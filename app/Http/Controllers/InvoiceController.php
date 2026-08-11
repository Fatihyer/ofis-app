<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Invoicedetail;
use App\Models\Kur;
use App\Models\Kdv;
use App\Models\Post;
use App\Models\Option;
use App\Models\Account;
use App\Models\Acente;
use App\Models\Hareket;
use App\Models\Sirket;
use App\Models\Hotel;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\DB;
use PDF;
///excel
use Maatwebsite\Excel\Facades\Excel;
use App\Http\Controllers\Controller;
use App\Exports\InvoicesExport;
use App\Services\PennylaneApiService;


class InvoiceController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
   public function __construct() {
    $this->middleware('auth');
    $this->middleware('permission:invoices.view')->only(['index', 'show', 'showpdf', 'voucherpdf', 'pennylaneCompare', 'providerPennylaneCompare']);
    $this->middleware('permission:invoices.create')->only(['create', 'store', 'autofacture']);
    $this->middleware('permission:invoices.update')->only(['edit', 'update']);
    $this->middleware('role:Superadmin')->only(['destroy', 'sendPennylaneDraft']);
    }
    public function index(Request $request)
{
    $query = Invoice::with(['sirket', 'acente', 'post', 'kur'])->sortable()->orderBy('id', 'desc');

    if ($request->filled('sirket_id')) {
        $query->where('sirket_id', $request->input('sirket_id'));
    }

    // Check if search query exists
    if ($request->has('search')) {
        $searchTerm = $request->input('search');
        $query->where(function($query) use ($searchTerm) {
            $query->where('tarih', 'like', '%' . $searchTerm . '%')
                  ->orWhere('post_id', 'like', '%' . $searchTerm . '%')
                  ->orWhere('amount', 'like', '%' . $searchTerm . '%')
                  ->orWhere('resmi', 'like', '%' . $searchTerm . '%')
                  ->orWhere('id', 'like', '%' . $searchTerm . '%');
        });
    }

    $invoices = $query->paginate(100)->appends($request->query());
    $sirkets = Sirket::orderBy('name')->pluck('name', 'id');
    $selectedSirketId = $request->input('sirket_id');

    return view('invoice.index', compact('invoices', 'sirkets', 'selectedSirketId'));
}

    public function pennylaneCompare(Request $request, PennylaneApiService $pennylane)
    {
        $sirkets = Sirket::whereIn('id', [2, 3])->orderBy('name')->pluck('name', 'id');
        $selectedSirketId = (int) $request->input('sirket_id', 2);
        if (!in_array($selectedSirketId, [2, 3], true)) {
            $selectedSirketId = 2;
        }

        $startDate = $request->input('start_date', now()->startOfYear()->toDateString());
        $endDate = $request->input('end_date', now()->toDateString());
        $statusFilter = $request->input('status', 'all');
        $accountKey = $this->pennylaneAccountForSirket($selectedSirketId);

        $laravelInvoices = Invoice::with(['acente', 'post'])
            ->where('sirket_id', $selectedSirketId)
            ->whereNull('deleted_at')
            ->whereBetween('tarih', [$startDate.' 00:00:00', $endDate.' 23:59:59'])
            ->whereNotNull('resmi')
            ->where('resmi', '!=', '')
            ->orderBy('tarih', 'desc')
            ->get();

        $pennylaneInvoices = $this->fetchPennylaneCustomerInvoices($pennylane, $accountKey, $startDate, $endDate);
        $pennylaneByNumber = [];
        foreach ($pennylaneInvoices as $invoice) {
            foreach ($this->invoiceNumberKeys($invoice['invoice_number'] ?? '', $invoice['date'] ?? null) as $number) {
                if ($number !== '' && !isset($pennylaneByNumber[$number])) {
                    $pennylaneByNumber[$number] = $invoice;
                }
            }
        }

        $laravelNumbers = [];
        $rows = [];
        $stats = [
            'ok' => 0,
            'missing_pennylane' => 0,
            'amount_mismatch' => 0,
            'date_mismatch' => 0,
            'extra_pennylane' => 0,
        ];

        foreach ($laravelInvoices as $invoice) {
            $numberKeys = $this->invoiceNumberKeys($invoice->resmi, substr((string) $invoice->tarih, 0, 10));
            $number = $numberKeys[0] ?? '';
            foreach ($numberKeys as $key) {
                $laravelNumbers[$key] = true;
            }
            $matchedNumber = null;
            $penny = null;
            foreach ($numberKeys as $key) {
                if (isset($pennylaneByNumber[$key])) {
                    $matchedNumber = $key;
                    $penny = $pennylaneByNumber[$key];
                    break;
                }
            }
            $status = 'ok';
            $amountDiff = null;
            $dateDiff = false;

            if (!$penny) {
                $status = 'missing_pennylane';
            } else {
                $laravelAmount = round((float) $invoice->amount, 2);
                $pennyAmount = round((float) ($penny['amount'] ?? 0), 2);
                $amountDiff = round($laravelAmount - $pennyAmount, 2);
                $dateDiff = substr((string) $invoice->tarih, 0, 10) !== ($penny['date'] ?? null);

                if (abs($amountDiff) > 0.01) {
                    $status = 'amount_mismatch';
                } elseif ($dateDiff) {
                    $status = 'date_mismatch';
                }
            }

            $stats[$status]++;
            $rows[] = [
                'status' => $status,
                'laravel' => $invoice,
                'pennylane' => $penny,
                'number' => $invoice->resmi,
                'matched_number' => $matchedNumber,
                'amount_diff' => $amountDiff,
                'date_diff' => $dateDiff,
            ];
        }

        $extraPennylaneIds = [];
        foreach ($pennylaneByNumber as $number => $penny) {
            $pennyId = $penny['id'] ?? ($penny['invoice_number'] ?? $number);
            if (!isset($laravelNumbers[$number])) {
                if (isset($extraPennylaneIds[$pennyId])) {
                    continue;
                }
                $extraPennylaneIds[$pennyId] = true;
                $stats['extra_pennylane']++;
                $rows[] = [
                    'status' => 'extra_pennylane',
                    'laravel' => null,
                    'pennylane' => $penny,
                    'number' => $penny['invoice_number'] ?? $number,
                    'matched_number' => null,
                    'amount_diff' => null,
                    'date_diff' => false,
                ];
            }
        }

        if ($statusFilter !== 'all') {
            $rows = array_values(array_filter($rows, fn ($row) => $row['status'] === $statusFilter));
        }

        usort($rows, function ($a, $b) {
            $aDate = $a['laravel'] ? substr((string) $a['laravel']->tarih, 0, 10) : ($a['pennylane']['date'] ?? '');
            $bDate = $b['laravel'] ? substr((string) $b['laravel']->tarih, 0, 10) : ($b['pennylane']['date'] ?? '');
            return strcmp($bDate, $aDate);
        });

        return view('invoice.pennylane_compare', compact(
            'sirkets',
            'selectedSirketId',
            'startDate',
            'endDate',
            'statusFilter',
            'accountKey',
            'rows',
            'stats',
            'laravelInvoices',
            'pennylaneInvoices'
        ));
    }

    public function providerPennylaneCompare(Request $request, PennylaneApiService $pennylane)
    {
        $sirkets = Sirket::whereIn('id', [2, 3])->orderBy('name')->pluck('name', 'id');
        $selectedSirketId = (int) $request->input('sirket_id', 2);
        if (!in_array($selectedSirketId, [2, 3], true)) {
            $selectedSirketId = 2;
        }

        $startDate = $request->input('start_date', now()->startOfMonth()->toDateString());
        $endDate = $request->input('end_date', now()->toDateString());
        $statusFilter = $request->input('status', 'all');
        $accountKey = $this->pennylaneAccountForSirket($selectedSirketId);
        $supplierMappings = $this->pennylaneTierMappings('supplier');

        $laravelMovements = Hareket::with(['acente', 'post'])
            ->whereNull('deleted_at')
            ->whereNotNull('invoiceno')
            ->whereRaw("TRIM(invoiceno) != ''")
            ->whereBetween('tarih', [$startDate.' 00:00:00', $endDate.' 23:59:59'])
            ->where('amount', '>', 0)
            ->where(function ($query) use ($selectedSirketId) {
                $query->where('sirket_id', $selectedSirketId)->orWhereNull('sirket_id');
            })
            ->orderBy('tarih', 'desc')
            ->get();

        $pennylaneInvoices = $this->fetchPennylaneSupplierInvoices($pennylane, $accountKey, $startDate, $endDate);
        $pennylaneBySupplierAndNumber = [];
        $pennylaneById = [];
        $unmappedSupplierIds = [];

        foreach ($pennylaneInvoices as $invoice) {
            $pennyId = $invoice['id'] ?? null;
            if ($pennyId) {
                $pennylaneById[$pennyId] = $invoice;
            }

            $supplierId = $this->pennylaneSupplierId($invoice);
            $mappedAcenteId = $supplierId ? ($supplierMappings[$this->mappingKey($accountKey, $supplierId)] ?? null) : null;

            if (!$mappedAcenteId) {
                if ($supplierId) {
                    $unmappedSupplierIds[$supplierId] = true;
                }
                continue;
            }

            foreach ($this->invoiceNumberKeys($invoice['invoice_number'] ?? '', $invoice['date'] ?? null) as $number) {
                $pennylaneBySupplierAndNumber[$mappedAcenteId.'|'.$number] = $invoice;
            }
        }

        $usedPennylaneIds = [];
        $rows = [];
        $stats = [
            'ok' => 0,
            'missing_pennylane' => 0,
            'extra_pennylane' => 0,
            'amount_mismatch' => 0,
            'date_mismatch' => 0,
            'unmapped_supplier' => 0,
            'company_missing' => 0,
        ];

        foreach ($laravelMovements as $movement) {
            $penny = null;
            $matchedNumber = null;
            $numberKeys = $this->invoiceNumberKeys($movement->invoiceno, substr((string) $movement->tarih, 0, 10));

            foreach ($numberKeys as $key) {
                $lookupKey = $movement->acente_id.'|'.$key;
                if (isset($pennylaneBySupplierAndNumber[$lookupKey])) {
                    $penny = $pennylaneBySupplierAndNumber[$lookupKey];
                    $matchedNumber = $key;
                    break;
                }
            }

            $status = 'ok';
            $amountDiff = null;
            $dateDiff = false;

            if (!$penny) {
                $status = 'missing_pennylane';
            } else {
                $pennyId = $penny['id'] ?? null;
                if ($pennyId) {
                    $usedPennylaneIds[$pennyId] = true;
                }

                $laravelAmount = round((float) $movement->amount, 2);
                $pennyAmount = round((float) ($penny['currency_amount'] ?? $penny['amount'] ?? 0), 2);
                $amountDiff = round($laravelAmount - $pennyAmount, 2);
                $dateDiff = substr((string) $movement->tarih, 0, 10) !== ($penny['date'] ?? null);

                if (abs($amountDiff) > 0.01) {
                    $status = 'amount_mismatch';
                } elseif ($dateDiff) {
                    $status = 'date_mismatch';
                }
            }

            if (!$movement->sirket_id) {
                $status = $status === 'ok' ? 'company_missing' : $status;
            }

            $stats[$status]++;
            $rows[] = [
                'status' => $status,
                'laravel' => $movement,
                'pennylane' => $penny,
                'number' => $movement->invoiceno,
                'matched_number' => $matchedNumber,
                'amount_diff' => $amountDiff,
                'date_diff' => $dateDiff,
                'supplier_id' => $this->pennylaneSupplierId($penny),
            ];
        }

        foreach ($pennylaneInvoices as $invoice) {
            $pennyId = $invoice['id'] ?? null;
            if ($pennyId && isset($usedPennylaneIds[$pennyId])) {
                continue;
            }

            $supplierId = $this->pennylaneSupplierId($invoice);
            $mappedAcenteId = $supplierId ? ($supplierMappings[$this->mappingKey($accountKey, $supplierId)] ?? null) : null;
            $status = $mappedAcenteId ? 'extra_pennylane' : 'unmapped_supplier';
            $stats[$status]++;

            $rows[] = [
                'status' => $status,
                'laravel' => null,
                'pennylane' => $invoice,
                'number' => $invoice['invoice_number'] ?? null,
                'matched_number' => null,
                'amount_diff' => null,
                'date_diff' => false,
                'supplier_id' => $supplierId,
                'mapped_acente_id' => $mappedAcenteId,
            ];
        }

        if ($statusFilter !== 'all') {
            $rows = array_values(array_filter($rows, fn ($row) => $row['status'] === $statusFilter));
        }

        usort($rows, function ($a, $b) {
            $aDate = $a['laravel'] ? substr((string) $a['laravel']->tarih, 0, 10) : ($a['pennylane']['date'] ?? '');
            $bDate = $b['laravel'] ? substr((string) $b['laravel']->tarih, 0, 10) : ($b['pennylane']['date'] ?? '');
            return strcmp($bDate, $aDate);
        });

        return view('invoice.provider_pennylane_compare', compact(
            'sirkets',
            'selectedSirketId',
            'startDate',
            'endDate',
            'statusFilter',
            'accountKey',
            'rows',
            'stats',
            'laravelMovements',
            'pennylaneInvoices',
            'supplierMappings',
            'unmappedSupplierIds'
        ));
    }

    private function fetchPennylaneCustomerInvoices(PennylaneApiService $pennylane, string $accountKey, string $startDate, string $endDate): array
    {
        $cursor = null;
        $items = [];
        $pages = 0;

        do {
            $query = ['limit' => 100];
            if ($cursor) {
                $query['cursor'] = $cursor;
            }

            $result = $pennylane->selectAccount($accountKey)->get('/customer_invoices', $query);
            if (!($result['ok'] ?? false)) {
                break;
            }

            $data = is_array($result['data'] ?? null) ? $result['data'] : [];
            foreach (($data['items'] ?? $data['data'] ?? []) as $invoice) {
                $date = $invoice['date'] ?? null;
                if (!$date || $date < $startDate || $date > $endDate) {
                    continue;
                }
                if (!empty($invoice['archived_at'])) {
                    continue;
                }
                $items[] = $invoice;
            }

            $cursor = $data['next_cursor'] ?? null;
            $pages++;
        } while ($cursor && $pages < 30);

        return $items;
    }

    private function fetchPennylaneSupplierInvoices(PennylaneApiService $pennylane, string $accountKey, string $startDate, string $endDate): array
    {
        $cursor = null;
        $items = [];
        $pages = 0;

        do {
            $query = ['limit' => 100];
            if ($cursor) {
                $query['cursor'] = $cursor;
            }

            $result = $pennylane->selectAccount($accountKey)->get('/supplier_invoices', $query);
            if (!($result['ok'] ?? false)) {
                break;
            }

            $data = is_array($result['data'] ?? null) ? $result['data'] : [];
            foreach (($data['items'] ?? $data['data'] ?? []) as $invoice) {
                $date = $invoice['date'] ?? null;
                if (!$date || $date < $startDate || $date > $endDate) {
                    continue;
                }
                if (!empty($invoice['archived_at'])) {
                    continue;
                }
                $items[] = $invoice;
            }

            $cursor = $data['next_cursor'] ?? null;
            $pages++;
        } while ($cursor && $pages < 30);

        return $items;
    }

    private function pennylaneSupplierId(?array $invoice): ?int
    {
        if (!$invoice) {
            return null;
        }

        $supplier = $invoice['supplier'] ?? null;
        if (is_array($supplier) && !empty($supplier['id'])) {
            return (int) $supplier['id'];
        }

        if (!empty($invoice['supplier_id'])) {
            return (int) $invoice['supplier_id'];
        }

        return null;
    }

    private function pennylaneTierMappings(string $type): array
    {
        $option = Option::where('name', 'pennylane_' . $type . '_mappings')->first();
        $value = $option ? json_decode((string) $option->value, true) : [];

        return is_array($value) ? $value : [];
    }

    private function mappingKey(string $accountKey, string|int $externalId): string
    {
        return $accountKey . ':' . $externalId;
    }

    private function normalizeInvoiceNumber($value): string
    {
        $value = trim((string) $value);
        $value = preg_replace('/\s+/', '', $value);
        $value = rtrim($value, '/');
        return strtoupper($value);
    }

    private function invoiceNumberKeys($value, ?string $date = null): array
    {
        $normalized = $this->normalizeInvoiceNumber($value);
        if ($normalized === '') {
            return [];
        }

        $keys = [];
        $add = function ($candidate) use (&$keys) {
            $candidate = $this->normalizeInvoiceNumber($candidate);
            if ($candidate === '') {
                return;
            }
            $keys[] = $candidate;
            if (str_starts_with($candidate, 'F')) {
                $keys[] = substr($candidate, 1);
            } else {
                $keys[] = 'F' . $candidate;
            }
        };
        $add($normalized);

        $year = $date ? substr($date, 0, 4) : null;
        $month = $date ? substr($date, 5, 2) : null;

        $addSequentialVariants = function ($candidate) use ($add) {
            if (preg_match('/^(F?\d{4}-\d{2}-)(\d{1,4})$/', $candidate, $matches)) {
                $add($matches[1] . str_pad($matches[2], 3, '0', STR_PAD_LEFT));
                $add($matches[1] . ltrim($matches[2], '0'));
            }
        };
        $addSequentialVariants($normalized);

        // Anciennes saisies: F20226-04-076 -> F2026-04-076.
        $fixedDuplicatedYearDigit = preg_replace('/^F?(20\d)2(\d-\d{2}-\d+)$/', 'F$1$2', $normalized);
        if ($fixedDuplicatedYearDigit && $fixedDuplicatedYearDigit !== $normalized) {
            $add($fixedDuplicatedYearDigit);
            $addSequentialVariants($fixedDuplicatedYearDigit);
        }

        // Saisies sans prefixe F: 2026-04-62 <-> F2026-04-62.
        if (preg_match('/^(\d{4}-\d{2}-\d{1,4})$/', $normalized, $matches)) {
            $add('F' . $matches[1]);
            $addSequentialVariants('F' . $matches[1]);
        }

        // Saisies sans mois: 2026-060 avec date 2026-04-20 -> F2026-04-060.
        if ($year && $month && preg_match('/^(F?\d{4})-(\d{1,4})$/', $normalized, $matches)) {
            $prefix = ltrim($matches[1], 'F');
            if ($prefix === $year) {
                $withMonth = 'F' . $year . '-' . $month . '-' . $matches[2];
                $add($withMonth);
                $addSequentialVariants($withMonth);
            }
        }

        foreach (array_values($keys) as $candidate) {
            $withoutSeparators = preg_replace('/[^A-Z0-9]/', '', $candidate);
            if ($withoutSeparators && $withoutSeparators !== $candidate) {
                $keys[] = $withoutSeparators;
            }
        }

        return array_values(array_unique(array_filter($keys)));
    }

    private function pennylaneAccountForSirket(int $sirketId): string
    {
        return [
            2 => 'parisvia',
            3 => 'francevia',
        ][$sirketId] ?? 'parisvia';
    }

    public function sendPennylaneDraft(Invoice $invoice, PennylaneApiService $pennylane)
    {
        $invoice->load(['acente', 'sirket', 'kur', 'invoicedetail.kdv']);

        if ($invoice->pennylane_customer_invoice_id) {
            return redirect()->back()->with('success', 'Cette facture est déjà envoyée dans Pennylane brouillon #' . $invoice->pennylane_customer_invoice_id . '.');
        }

        if ($invoice->avoir) {
            return redirect()->back()->withErrors('Les avoirs doivent être envoyés avec un flux Pennylane séparé.');
        }

        $accountKey = $this->pennylaneAccountForSirket((int) $invoice->sirket_id);
        $customerId = $this->pennylaneCustomerIdForInvoice($invoice, $accountKey);

        if (!$customerId) {
            return redirect()
                ->route('pennylane.index', ['account' => $accountKey, 'resource' => 'customers', 'limit' => 100])
                ->withErrors('Client non jumelé avec Pennylane. Associez le client puis relancez l’envoi.');
        }

        $productId = $this->pennylaneProductId($pennylane, $accountKey, 'Transferts Multiples');
        if (!$productId) {
            return redirect()->back()->withErrors('Produit Pennylane "Transferts Multiples" introuvable pour ce compte.');
        }

        $payload = $this->pennylaneDraftPayload($invoice, $customerId, $productId);
        if (empty($payload['invoice_lines'])) {
            return redirect()->back()->withErrors('Aucune ligne de facture positive à envoyer à Pennylane.');
        }

        $result = $pennylane->selectAccount($accountKey)->post('/customer_invoices', $payload);
        if (!($result['ok'] ?? false)) {
            return redirect()->back()->withErrors('Erreur Pennylane: ' . ($result['error'] ?? 'réponse inconnue'));
        }

        $data = is_array($result['data'] ?? null) ? $result['data'] : [];
        $created = is_array($data['data'] ?? null) ? $data['data'] : $data;
        $pennylaneId = $created['id'] ?? $created['customer_invoice_id'] ?? null;

        if (!$pennylaneId) {
            return redirect()->back()->withErrors('Pennylane a répondu sans identifiant de facture.');
        }

        $invoice->pennylane_customer_invoice_id = (string) $pennylaneId;
        $invoice->pennylane_customer_invoice_status = (string) ($created['status'] ?? 'draft');
        $invoice->pennylane_synced_at = now();
        $invoice->pennylane_payload = json_encode($payload, JSON_UNESCAPED_UNICODE);
        $invoice->save();

        return redirect()->back()->with('success', 'Brouillon Pennylane créé: #' . $pennylaneId . '.');
    }

    private function pennylaneCustomerIdForInvoice(Invoice $invoice, string $accountKey): ?string
    {
        $mappings = $this->pennylaneTierMappings('customer');
        foreach ($mappings as $mappingKey => $acenteId) {
            if ((int) $acenteId !== (int) $invoice->acente_id) {
                continue;
            }

            [$mappedAccount, $customerId] = array_pad(explode(':', (string) $mappingKey, 2), 2, null);
            if ($mappedAccount === $accountKey && $customerId) {
                return (string) $customerId;
            }
        }

        return null;
    }

    private function pennylaneProductId(PennylaneApiService $pennylane, string $accountKey, string $label): ?int
    {
        $result = $pennylane->selectAccount($accountKey)->get('/products', ['limit' => 100]);
        if (!($result['ok'] ?? false)) {
            return null;
        }

        $items = $result['data']['items'] ?? $result['data']['data'] ?? [];
        foreach ($items as $product) {
            if (!empty($product['archived_at'])) {
                continue;
            }
            if (mb_strtolower(trim((string) ($product['label'] ?? ''))) === mb_strtolower($label)) {
                return (int) $product['id'];
            }
        }

        return null;
    }

    private function pennylaneDraftPayload(Invoice $invoice, string $customerId, int $productId): array
    {
        $date = substr((string) $invoice->tarih, 0, 10) ?: now()->toDateString();
        $deadline = \Carbon\Carbon::parse($date)->addDays(30)->toDateString();

        $lines = $invoice->invoicedetail
            ->map(function ($line) use ($productId) {
                $amount = round((float) $line->amount, 2);
                if ($amount <= 0) {
                    return null;
                }

                $description = trim((string) $line->comments);
                return [
                    'product_id' => $productId,
                    'label' => 'Transferts Multiples',
                    'description' => $description ?: 'Ligne facture #' . $line->id,
                    'quantity' => 1,
                    'unit' => 'piece',
                    'raw_currency_unit_price' => number_format($amount, 2, '.', ''),
                    'vat_rate' => 'FR_100',
                ];
            })
            ->filter()
            ->values()
            ->all();

        return [
            'customer_id' => (int) $customerId,
            'date' => $date,
            'deadline' => $deadline,
            'draft' => true,
            'external_reference' => 'Laravel invoice #' . $invoice->id . ($invoice->resmi ? ' / ' . $invoice->resmi : ''),
            'invoice_lines' => $lines,
        ];
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //print_r($request->input());
        $invoice= New Invoice;
        $invoice->sirket_id=$request->input('sirket_id');
        $invoice->kur_id=$request->input('kur_id');
        $invoice->acente_id=$request->input('acente_id');
        $invoice->tarih=$request->input('tarih');
        $invoice->post_id=$request->input('post_id');
        $invoice->detail=serialize($request->input('detail'));
        $invoice->resmi=$request->input('resmi');
        $invoice->lang=$request->input('lang');
        $invoice->yazi=$request->input('yazi');
        $invoice->account_id=$request->input('account_id');
        $invoice->amount=array_sum($request->input('amount'));
        
        $invoice->save();
        $this->markPostAsInvoiced($invoice->post_id);
        //hareket ekle
        // HAREKET ZATEN VAR MI KONTROL ET (App\Invoice ilişkili)
            $existing = $invoice->harekets()
                ->where('hareketable_id', $invoice->id)
                ->where('hareketable_type', Invoice::class)
                ->first();

            if (!$existing) {
                $invoice->harekets()->create([
                    'sirket_id' => $invoice->sirket_id,
                    'aciklama'  => 'invoice no:' . $invoice->id,
                    'tarih'     => $invoice->tarih,
                    'post_id'   => $invoice->post_id,
                    'amount'    => $invoice->amount,
                    'ab'        => '1',
                    'kur_id'    => $invoice->kur_id,
                    'acente_id' => $invoice->acente_id,
                ]);
            }

        
         $count=count($request->comments);
         $comments=$request->comments;
         $kdv_id=$request->kdv_id;
         $amount=$request->amount;
        
        
        for($i = 0; $i < $count; $i++){
             $invoicedetails = new Invoicedetail;
             $invoicedetails->invoice_id=$invoice->id;
             $invoicedetails->post_id=$invoice->post_id;
             $invoicedetails->comments=$comments[$i];
             $invoicedetails->kdv_id=$kdv_id[$i];
             $invoicedetails->amount=$amount[$i]; 
             $invoicedetails->save(); 
        }  
       return Redirect::back()->withErrors(['The Invoice Added']);    
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Invoice  $invoice
     * @return \Illuminate\Http\Response
     */
    public function show(Invoice $invoice)
    {
        $invoice->load(['sirket', 'acente', 'post', 'kur', 'account', 'invoicedetail.kdv']);
        $detail = @unserialize($invoice->detail);
        if (!is_array($detail)) {
            $detail = [];
        }

        return view('invoice.show', compact('invoice', 'detail'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Invoice  $invoice
     * @return \Illuminate\Http\Response
     */
    public function edit(Invoice $invoice)
    {
       $acentes=Acente::orderby('name')->pluck('name','id')->toArray() ;  
       $sirkets=Sirket::orderby('name')->pluck('name','id')->toArray() ;  
       $invoices=Invoice::findOrFail($invoice->id);
       $detail=unserialize($invoices->detail); 
       $kurs =Kur::pluck('short_name', 'id')->toArray() ; 
       $kdvs =Kdv::pluck('name', 'id')->toArray() ;
       $accounts =Account::pluck('name', 'id')->toArray() ; 
       $canEditOfficialInvoiceNumber = $this->canEditOfficialInvoiceNumber($invoices);

       return view ('invoice.edit', compact('invoices','kurs','kdvs','detail','accounts','acentes','sirkets','canEditOfficialInvoiceNumber'));
}
      
    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Invoice  $invoice
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
{
    $invoice = Invoice::findOrFail($id);
    $oldOfficialInvoiceNumber = trim((string) $invoice->resmi);
    $requestedOfficialInvoiceNumber = trim((string) $request->input('resmi'));
    $officialInvoiceNumberChanged = $oldOfficialInvoiceNumber !== $requestedOfficialInvoiceNumber;
    $officialInvoiceNumberLocked = $oldOfficialInvoiceNumber !== '' && !$this->canEditOfficialInvoiceNumber($invoice);

    // Fatura bilgilerini güncelle
    $invoice->sirket_id   = $request->input('sirket_id');
    $invoice->kur_id      = $request->input('kur_id');
    $invoice->acente_id   = $request->input('acente_id');
    $invoice->tarih       = $request->input('tarih');
    $invoice->detail      = serialize($request->input('detail'));
    $invoice->resmi       = ($officialInvoiceNumberLocked && $officialInvoiceNumberChanged)
        ? $oldOfficialInvoiceNumber
        : $requestedOfficialInvoiceNumber;
    $invoice->lang        = $request->input('lang');
    $invoice->yazi        = $request->input('yazi');
    $invoice->avoir       = $request->input('avoir');
    $invoice->account_id  = $request->input('account_id');
    $total = array_sum($request->input('amount'));
    $invoice->amount = abs($total);
    $invoice->save();
    $this->markPostAsInvoiced($invoice->post_id);

    // İlgili açıklamaya sahip hareket varsa güncelle, yoksa oluştur
    $existingHareket = $invoice->harekets()
    ->where('hareketable_id', $invoice->id)
    ->where('hareketable_type', Invoice::class)
    ->first();

$sign = $invoice->avoir ? -1 : 1;

if ($existingHareket) {
    $existingHareket->update([
        'sirket_id' => $invoice->sirket_id,
        'aciklama'  => 'invoice no:' . $invoice->id,
        'tarih'     => $invoice->tarih,
        'post_id'   => $invoice->post_id,
        'amount'    => $invoice->amount,
          'ab'        => $invoice->avoir ? '2' : '1',
        'kur_id'    => $invoice->kur_id,
        'acente_id' => $invoice->acente_id,
    ]);
} else {
    $invoice->harekets()->create([
        'sirket_id' => $invoice->sirket_id,
        'aciklama'  => 'invoice no:' . $invoice->id,
        'tarih'     => $invoice->tarih,
        'post_id'   => $invoice->post_id,
        'amount'    => $invoice->amount,
        'ab'        => $invoice->avoir ? '2' : '1',
        'kur_id'    => $invoice->kur_id,
        'acente_id' => $invoice->acente_id,
    ]);
}
    // Eski detayları sil
    Invoicedetail::where('invoice_id', $id)->delete();

    // Yeni detayları ekle
    $comments = $request->comments;
    $kdv_id   = $request->kdv_id;
    $amounts  = $request->amount;

    if (is_array($comments) && is_array($amounts)) {
    foreach ($comments as $i => $comment) {
        if (!isset($amounts[$i]) || !is_numeric($amounts[$i])) {
            continue; // amount eksikse o satırı atla
        }

        Invoicedetail::create([
            'invoice_id' => $invoice->id,
            'post_id'    => $invoice->post_id,
            'comments'   => $comment,
            'kdv_id'     => $kdv_id[$i] ?? null,
            'amount'     => $amounts[$i],
        ]);
    }
}
    $message = ($officialInvoiceNumberLocked && $officialInvoiceNumberChanged)
        ? 'Facture mise à jour, mais le N° officiel est réservé au Superadmin et à la Compta.'
        : 'Fatura ve hareket başarıyla güncellendi.';

    return redirect()->back()->with('success', $message);
}

    private function canEditOfficialInvoiceNumber(?Invoice $invoice = null): bool
    {
        $user = auth()->user();

        if (!$user) {
            return false;
        }

        if ($user->hasAnyRole(['Superadmin', 'Compta'])) {
            return true;
        }

        return !$invoice || trim((string) $invoice->resmi) === '';
    }

    public function destroy(Invoice $invoice)
    {
        if (!auth()->user() || !auth()->user()->hasRole('Superadmin')) {
            abort(403, 'Seul un Superadmin peut supprimer une facture.');
        }

        DB::transaction(function () use ($invoice) {
            $invoiceId = $invoice->id;
            $postId = $invoice->post_id;

            $invoice->harekets()->delete();
            Invoicedetail::where('invoice_id', $invoiceId)->delete();
            $invoice->delete();

            if ($postId) {
                $hasActiveInvoice = Invoice::where('post_id', $postId)
                    ->whereNull('deleted_at')
                    ->exists();

                if (!$hasActiveInvoice && \Illuminate\Support\Facades\Schema::hasColumn('posts', 'billing_status')) {
                    Post::where('id', $postId)->update(['billing_status' => 'to_invoice']);
                }
            }
        });

        return Redirect::back()->with('flash_message', 'Facture supprimée avec ses mouvements.');
    }
  
      
  public function showpdf($id, $type = false)
{
    $invoices = Invoice::findOrFail($id);

    $officialInvoiceNumber = trim((string) $invoices->resmi);
    $filename = $officialInvoiceNumber !== ''
        ? 'F-' . $officialInvoiceNumber . '.pdf'
        : 'Proforma-' . $invoices->id . '.pdf';

    // Detayları deserialize et
    $invoices->detail = unserialize($invoices->detail);

    if ($type === 'pdf') {
        $pdf = PDF::loadView('invoice.pdf', compact('invoices'));
        return $pdf->download($filename);
    } elseif ($type === 'xls') {
        return Excel::download(new InvoicesExport($invoice), 'invoice.xlsx');
    } elseif ($type === 'pdf2') {
        $pdf = PDF::loadView('invoice.pdf2', compact('invoices'))->setPaper('a4');
        return $pdf->download($filename);
    } else {
        return view('invoice.pdf', compact('invoices'));
    }
}

  public function autofacture(Request $request, $id)
{
    $request->validate([
        'sirket_id' => 'required|integer|exists:sirkets,id',
    ]);

    $post = Post::findOrFail($id);

    $invoice = new Invoice;
    $invoice->kur_id      = 1;
    $invoice->sirket_id   = (int) $request->input('sirket_id');
    $invoice->post_id     = $post->id;
    $invoice->acente_id   = $post->acente_id;
    $invoice->tarih       = date("Y-m-d");
    $invoice->lang        = 1;
    $invoice->yazi        = 1;
    $invoice->account_id  = 10;
    $invoice->amount      = 0;

    $detail = [
        "tittle"       => $post->acente->tittle,
        "address"      => $post->acente->address,
        "postal"       => $post->acente->postal,
        "city"         => $post->acente->city,
        "country_name" => $post->acente->country_name,
        "vd"           => $post->acente->vd,
        "vdno"         => $post->acente->vdno,
        "not"          => "MERCI D'EFFECTUER LE PAIEMENT PAR VIREMENT BANCAIRE."
    ];

    $invoice->detail = serialize($detail);
    $invoice->save();

    $amount = 0;

    foreach ($post->transfer as $transfer) {
        $service    = $transfer->servicetype->name;
        $from       = $transfer->from;
        $start_date = $transfer->start_date;

        foreach ($transfer->harekets as $masraf) {
            $invoicedetails = new Invoicedetail;
            $invoicedetails->invoice_id = $invoice->id;
            $invoicedetails->post_id    = $post->id;
            $invoicedetails->comments   = $service . " " . date('d-m-Y H:i', strtotime($start_date)) . " " . $from;
            $invoicedetails->kdv_id     = false;
            $invoicedetails->amount     = $masraf->default_price;
            $invoicedetails->save();

            $amount += $masraf->default_price;
        }
    }

    // HAREKET ZATEN VARSA OLUŞTURMA
    $existing = $invoice->harekets()
        ->where('hareketable_id', $invoice->id)
        ->where('hareketable_type', Invoice::class)
        ->first();

    if (!$existing) {
        $invoice->harekets()->create([
            'sirket_id' => $invoice->sirket_id,
            'aciklama'  => 'invoice no:' . $invoice->id,
            'tarih'     => $invoice->tarih,
            'post_id'   => $invoice->post_id,
            'amount'    => $amount,
            'ab'        => '1',
            'kur_id'    => 1,
            'acente_id' => $invoice->acente_id,
        ]);
    }

    // Invoice amount güncelle
    $invoice->amount = $amount;
    $invoice->save();
    $this->markPostAsInvoiced($invoice->post_id);

    return Redirect::back()->with('flash_message', 'Invoice created');
}

    
    private function markPostAsInvoiced(?int $postId): void
    {
        if (!$postId) {
            return;
        }

        $post = Post::find($postId);
        if (!$post || $post->billing_status === 'do_not_invoice') {
            return;
        }

        $hasOfficialInvoice = Invoice::where('post_id', $postId)
            ->whereNull('deleted_at')
            ->whereNotNull('resmi')
            ->whereRaw("TRIM(resmi) != ''")
            ->exists();

        $post->update([
            'billing_status' => $hasOfficialInvoice ? 'invoiced' : 'to_invoice',
        ]);
    }
    }
