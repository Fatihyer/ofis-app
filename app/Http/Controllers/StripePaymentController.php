<?php

namespace App\Http\Controllers;

use App\Models\Acente;
use App\Models\BankImport;
use App\Models\Option;
use App\Models\Sirket;
use App\Models\StripePayment;
use App\Services\OffsetService;
use App\Services\StripeApiService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class StripePaymentController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth')->except(['webhook']);
        $this->middleware('permission:balances.view')->except(['webhook']);
    }

    public function index(Request $request)
    {
        $selectedSirketId = (int) $request->input('sirket_id', 2);
        $startDate = $request->input('start_date', now()->subDays(30)->toDateString());
        $endDate = $request->input('end_date', now()->toDateString());
        $status = $request->input('status', 'pending');

        $query = StripePayment::with(['acente', 'post', 'offset'])
            ->when($selectedSirketId, fn ($q) => $q->where('sirket_id', $selectedSirketId))
            ->whereBetween('paid_at', [$startDate.' 00:00:00', $endDate.' 23:59:59'])
            ->orderBy('paid_at', 'desc');

        if ($status === 'pending') {
            $query->whereNull('offset_id')
                ->whereNotExists(function ($subquery) {
                    $subquery->select(DB::raw(1))
                        ->from('stripe_payments as matched_stripe_payments')
                        ->whereNotNull('matched_stripe_payments.offset_id')
                        ->whereColumn('matched_stripe_payments.id', '<>', 'stripe_payments.id')
                        ->whereRaw('COALESCE(matched_stripe_payments.payment_intent_id, matched_stripe_payments.stripe_id) = COALESCE(stripe_payments.payment_intent_id, stripe_payments.stripe_id)');
                });
        } elseif ($status === 'posted') {
            $query->whereNotNull('offset_id');
        }

        $payments = $query->paginate(100)->appends($request->query());
        $postedIntentKeys = StripePayment::whereNotNull('offset_id')
            ->selectRaw('COALESCE(payment_intent_id, stripe_id) as intent_key')
            ->pluck('intent_key')
            ->filter()
            ->flip();
        $sirkets = Sirket::whereIn('id', [2, 3])->orderBy('name')->pluck('name', 'id');
        $acenteler = Acente::orderBy('name')->pluck('name', 'id');
        $stripeAccounts = $this->stripeAccountOptions();

        return view('stripe.payments', compact(
            'payments',
            'sirkets',
            'acenteler',
            'stripeAccounts',
            'selectedSirketId',
            'startDate',
            'endDate',
            'status',
            'postedIntentKeys'
        ));
    }

    public function sync(Request $request, StripeApiService $stripe)
    {
        $validated = $request->validate([
            'sirket_id' => ['required', 'integer'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
        ]);

        $start = Carbon::parse($validated['start_date'])->startOfDay();
        $end = Carbon::parse($validated['end_date'])->endOfDay();
        $accountKey = $this->stripeAccountForSirket((int) $validated['sirket_id']);
        $result = $stripe->selectAccount($accountKey)->listSucceededPaymentIntents($start->timestamp, $end->timestamp);

        if (!($result['ok'] ?? false)) {
            return back()->with('error', 'Erreur Stripe: '.($result['error'] ?? 'réponse inconnue'));
        }

        $created = 0;
        $updated = 0;
        $alreadyPosted = 0;

        foreach ($result['items'] as $paymentIntent) {
            $payment = $this->storePaymentIntent($paymentIntent, (int) $validated['sirket_id'], $accountKey);
            if ($payment->offset_id) {
                $alreadyPosted++;
                continue;
            }
            $payment->wasRecentlyCreated ? $created++ : $updated++;
        }

        return redirect()
            ->route('stripe.payments.index', [
                'sirket_id' => $validated['sirket_id'],
                'start_date' => $validated['start_date'],
                'end_date' => $validated['end_date'],
                'status' => 'pending',
            ])
            ->with('success', 'Stripe récupéré: '.$created.' nouveau(x), '.$updated.' mis à jour, '.$alreadyPosted.' déjà comptabilisé(s).');
    }

    public function createOffset(Request $request, StripePayment $stripePayment)
    {
        $validated = $request->validate([
            'a_acente_id' => ['required', 'integer', 'different:b_acente_id'],
            'b_acente_id' => ['required', 'integer'],
            'kur_id' => ['required', 'integer'],
            'aciklama' => ['required', 'string'],
        ]);

        if ($stripePayment->offset_id) {
            return back()->with('error', 'Ce paiement Stripe est déjà comptabilisé.');
        }

        DB::transaction(function () use ($validated, $stripePayment) {
            $offset = OffsetService::createFromBank(
                (int) $validated['a_acente_id'],
                (int) $validated['b_acente_id'],
                (float) $stripePayment->amount,
                (int) $validated['kur_id'],
                $validated['aciklama'],
                optional($stripePayment->paid_at)->toDateString() ?: now()->toDateString(),
                (int) ($stripePayment->post_id ?: 0),
                (int) ($stripePayment->sirket_id ?: 2)
            );

            $stripePayment->update([
                'acente_id' => (int) $validated['b_acente_id'],
                'offset_id' => $offset->id,
            ]);
        });

        return back()->with('success', 'Paiement Stripe comptabilisé.');
    }

    public function webhook(Request $request, ?string $account = null)
    {
        $payload = $request->getContent();
        $signature = (string) $request->header('Stripe-Signature');
        $account = in_array($account, ['parisvia', 'francevia'], true) ? $account : null;
        $secret = $account
            ? (string) config('services.stripe.accounts.'.$account.'.webhook_secret')
            : null;

        if ($secret && !$this->validSignature($payload, $signature, $secret)) {
            return response('Signature invalide', 400);
        }

        if (!$secret && !$this->validAnySignature($payload, $signature)) {
            return response('Signature invalide', 400);
        }

        $event = json_decode($payload, true);
        if (!is_array($event)) {
            return response('Payload invalide', 400);
        }

        try {
            $type = $event['type'] ?? '';
            $object = data_get($event, 'data.object', []);

            if ($type === 'payment_intent.succeeded' && is_array($object)) {
                $sirketId = $account
                    ? $this->sirketForStripeAccount($account)
                    : (int) ($object['metadata']['sirket_id'] ?? config('services.stripe.default_sirket_id', 2));
                $this->storePaymentIntent($object, $sirketId, $account ?: $this->stripeAccountForSirket($sirketId));
            }

            if ($type === 'charge.refunded' && is_array($object)) {
                $paymentIntentId = $object['payment_intent'] ?? null;
                if ($paymentIntentId) {
                    StripePayment::where('payment_intent_id', $paymentIntentId)->update(['status' => 'refunded']);
                }
            }
        } catch (\Throwable $e) {
            Log::warning('Stripe webhook failed', ['message' => $e->getMessage()]);
            return response('Erreur webhook', 500);
        }

        return response('ok', 200);
    }

    private function storePaymentIntent(array $paymentIntent, int $sirketId, ?string $accountKey = null): StripePayment
    {
        $amount = ((int) ($paymentIntent['amount_received'] ?? $paymentIntent['amount'] ?? 0)) / 100;
        $createdAt = Carbon::createFromTimestamp((int) ($paymentIntent['created'] ?? time()));
        $stripeId = (string) ($paymentIntent['id'] ?? '');
        $accountKey = $accountKey ?: $this->stripeAccountForSirket($sirketId);

        $payload = [
            'stripe_account' => $accountKey,
            'payment_intent_id' => $stripeId,
            'sirket_id' => $sirketId,
            'amount' => $amount,
            'currency' => strtoupper((string) ($paymentIntent['currency'] ?? 'EUR')),
            'status' => $paymentIntent['status'] ?? 'succeeded',
            'customer_email' => $this->stripeCustomerEmail($paymentIntent),
            'description' => $paymentIntent['description'] ?? data_get($paymentIntent, 'metadata.description'),
            'paid_at' => $createdAt,
            'raw' => $paymentIntent,
        ];

        $existing = StripePayment::where(function ($query) use ($stripeId) {
                $query->where('payment_intent_id', $stripeId)
                    ->orWhere('stripe_id', $stripeId);
            })
            ->orderByRaw('offset_id IS NULL')
            ->first();

        if ($existing) {
            $existing->fill($payload + ['stripe_id' => $existing->stripe_id ?: $stripeId]);
            $existing->save();

            return $existing;
        }

        return StripePayment::create($payload + [
            'stripe_id' => $stripeId,
        ]);
    }

    private function stripeCustomerEmail(array $paymentIntent): ?string
    {
        foreach ([
            'receipt_email',
            'customer.email',
            'latest_charge.billing_details.email',
            'charges.data.0.billing_details.email',
            'metadata.email',
            'metadata.customer_email',
            'metadata.client_email',
        ] as $path) {
            $email = data_get($paymentIntent, $path);
            if (is_string($email) && trim($email) !== '') {
                return trim($email);
            }
        }

        return null;
    }

    private function validSignature(string $payload, string $signature, string $secret): bool
    {
        $parts = [];
        foreach (explode(',', $signature) as $item) {
            [$key, $value] = array_pad(explode('=', $item, 2), 2, null);
            if ($key && $value) {
                $parts[$key][] = $value;
            }
        }

        $timestamp = $parts['t'][0] ?? null;
        $signatures = $parts['v1'] ?? [];
        if (!$timestamp || !$signatures) {
            return false;
        }

        $expected = hash_hmac('sha256', $timestamp.'.'.$payload, $secret);
        foreach ($signatures as $signatureValue) {
            if (hash_equals($expected, $signatureValue)) {
                return true;
            }
        }

        return false;
    }

    private function validAnySignature(string $payload, string $signature): bool
    {
        $secrets = array_filter([
            config('services.stripe.webhook_secret'),
            config('services.stripe.accounts.parisvia.webhook_secret'),
            config('services.stripe.accounts.francevia.webhook_secret'),
        ]);

        foreach (array_unique($secrets) as $secret) {
            if ($this->validSignature($payload, $signature, (string) $secret)) {
                return true;
            }
        }

        return empty($secrets);
    }

    private function stripeAccountOptions(): array
    {
        $finansId = Option::where('name', 'finansid')->value('value');

        return Acente::when($finansId, function ($query) use ($finansId) {
                $query->whereHas('firmas', fn ($q) => $q->where('firmas.id', $finansId));
            })
            ->orderBy('name')
            ->pluck('name', 'id')
            ->toArray();
    }

    private function stripeAccountForSirket(int $sirketId): string
    {
        return [
            2 => 'parisvia',
            3 => 'francevia',
        ][$sirketId] ?? 'parisvia';
    }

    private function sirketForStripeAccount(string $account): int
    {
        return [
            'parisvia' => 2,
            'francevia' => 3,
        ][$account] ?? 2;
    }
}
