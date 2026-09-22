<?php

namespace App\Http\Controllers;

use App\Models\TicketSale;
use App\Models\WhatsAppGroup;
use App\Models\WhatsAppGroupMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class TicketSaleController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('permission:invoices.view');
    }

    public function index(Request $request)
    {
        $from = $request->date('from') ?: Carbon::parse(TicketSale::min('sale_date') ?: now()->subMonths(3));
        $to = $request->date('to') ?: now();
        $product = $request->query('product');
        $onlyOpen = $request->boolean('open');

        $sales = TicketSale::query()
            ->whereBetween('sale_date', [$from->toDateString(), $to->toDateString()])
            ->when($product, fn ($q) => $q->where('product', $product))
            ->get();

        $acenteIds = $sales->pluck('acente_id')->filter()->unique()->all();
        $invoiceTotals = $this->invoiceTotals($acenteIds, $from, $to);
        $acenteNames = DB::table('acentes')->whereIn('id', $acenteIds ?: [0])->pluck('name', 'id');
        $collectionHits = $this->collectionHits($sales->pluck('customer_key')->unique()->all(), $from, $to);

        // Ayni kisinin farkli yazimlari acente kaydi uzerinden tek satirda toplanir
        $rows = $sales->groupBy(fn ($sale) => $sale->acente_id ? 'a' . $sale->acente_id : 'k' . $sale->customer_key)
            ->map(function ($group) use ($invoiceTotals, $acenteNames, $collectionHits) {
            $acenteId = $group->pluck('acente_id')->filter()->first();
            $key = $group->first()->customer_key;
            $amount = (float) $group->sum('amount');
            $invoiced = $acenteId ? (float) ($invoiceTotals[$acenteId]['amount'] ?? 0) : 0.0;

            return [
                'customer_key' => $key,
                'customer_raw' => $group->first()->customer_raw,
                'acente_id' => $acenteId,
                'acente_name' => $acenteId ? ($acenteNames[$acenteId] ?? null) : null,
                'tickets' => (int) $group->sum(fn ($s) => $s->qty_adult + $s->qty_child),
                'amount' => $amount,
                'lines' => $group->count(),
                'first_date' => $group->min('sale_date'),
                'last_date' => $group->max('sale_date'),
                'invoiced' => $invoiced,
                'invoice_count' => $acenteId ? (int) ($invoiceTotals[$acenteId]['count'] ?? 0) : 0,
                'cash_hits' => $collectionHits[$key] ?? 0,
                'gap' => $amount - $invoiced,
            ];
        })->values();

        // Faturasi ve nakit tahsilat kaydi olmayanlar once
        $rows = $rows->sortByDesc(function ($row) {
            $open = $row['invoice_count'] === 0 && $row['cash_hits'] === 0;

            return ($open ? 1_000_000 : 0) + $row['amount'];
        })->values();

        if ($onlyOpen) {
            $rows = $rows->filter(fn ($r) => $r['invoice_count'] === 0 && $r['cash_hits'] === 0)->values();
        }

        $summary = [
            'tickets' => (int) $rows->sum('tickets'),
            'amount' => (float) $rows->sum('amount'),
            'open_amount' => (float) $rows->where('invoice_count', 0)->where('cash_hits', 0)->sum('amount'),
            'open_count' => $rows->where('invoice_count', 0)->where('cash_hits', 0)->count(),
            'customers' => $rows->count(),
        ];

        $products = TicketSale::select('product')->distinct()->orderBy('product')->pluck('product');

        return view('tickets.index', compact('rows', 'summary', 'from', 'to', 'product', 'products', 'onlyOpen'));
    }

    public function show(Request $request, string $customerKey)
    {
        $acenteId = TicketSale::where('customer_key', $customerKey)->value('acente_id');

        // Acente eslesmisse ayni acenteye ait tum yazimlar birlikte gosterilir
        $sales = TicketSale::with(['group', 'message'])
            ->when($acenteId, fn ($q) => $q->where('acente_id', $acenteId), fn ($q) => $q->where('customer_key', $customerKey))
            ->orderBy('sale_date')
            ->get();

        abort_if($sales->isEmpty(), 404);
        $invoices = $acenteId
            ? DB::table('invoices')
                ->where('acente_id', $acenteId)
                ->whereNull('deleted_at')
                ->whereBetween('tarih', [$sales->min('sale_date')->copy()->subDays(15), $sales->max('sale_date')->copy()->addDays(45)])
                ->orderBy('tarih')
                ->get(['id', 'tarih', 'amount'])
            : collect();

        $collections = $this->collectionMessages($customerKey);

        return view('tickets.show', compact('sales', 'invoices', 'collections', 'customerKey'));
    }

    /** @return array<int, array{name: string, amount: float, count: int}> */
    private function invoiceTotals(array $acenteIds, Carbon $from, Carbon $to): array
    {
        if (empty($acenteIds)) {
            return [];
        }

        return DB::table('invoices')
            ->join('acentes', 'acentes.id', '=', 'invoices.acente_id')
            ->whereIn('invoices.acente_id', $acenteIds)
            ->whereNull('invoices.deleted_at')
            ->whereBetween('invoices.tarih', [$from->copy()->subDays(15), $to->copy()->addDays(45)])
            ->groupBy('invoices.acente_id', 'acentes.name')
            ->get([
                'invoices.acente_id',
                'acentes.name',
                DB::raw('sum(invoices.amount) as total'),
                DB::raw('count(*) as adet'),
            ])
            ->keyBy('acente_id')
            ->map(fn ($row) => ['name' => $row->name, 'amount' => (float) $row->total, 'count' => (int) $row->adet])
            ->all();
    }

    /** Tahsilat grubunda musteri adi gecen mesaj sayisi @return array<string, int> */
    private function collectionHits(array $customerKeys, Carbon $from, Carbon $to): array
    {
        $group = WhatsAppGroup::where('name', 'like', '%Tahsilat%')->first();

        if (!$group) {
            return [];
        }

        $messages = WhatsAppGroupMessage::where('whatsapp_group_id', $group->id)
            ->whereBetween('sent_at', [$from->copy()->startOfDay(), $to->copy()->addDays(45)->endOfDay()])
            ->pluck('body');

        $hits = [];

        foreach ($customerKeys as $key) {
            $needle = $this->firstNamePart($key);

            if ($needle === '') {
                continue;
            }

            $hits[$key] = $messages->filter(fn ($body) => $body && mb_stripos($this->fold($body), $needle) !== false)->count();
        }

        return $hits;
    }

    private function collectionMessages(string $customerKey)
    {
        $group = WhatsAppGroup::where('name', 'like', '%Tahsilat%')->first();
        $needle = $this->firstNamePart($customerKey);

        if (!$group || $needle === '') {
            return collect();
        }

        return WhatsAppGroupMessage::where('whatsapp_group_id', $group->id)
            ->orderBy('sent_at')
            ->get(['sender_name', 'body', 'sent_at'])
            ->filter(fn ($m) => $m->body && mb_stripos($this->fold($m->body), $needle) !== false)
            ->values();
    }

    /** Eslestirme icin ayirt edici ad parcasi (en uzun kelime) */
    private function firstNamePart(string $key): string
    {
        $parts = array_filter(explode(' ', $key), fn ($p) => mb_strlen($p) >= 4);

        if (empty($parts)) {
            return '';
        }

        usort($parts, fn ($a, $b) => mb_strlen($b) <=> mb_strlen($a));

        return $parts[0];
    }

    private function fold(string $value): string
    {
        return strtr(mb_strtolower($value, 'UTF-8'), [
            'ı' => 'i', 'ş' => 's', 'ğ' => 'g', 'ü' => 'u', 'ö' => 'o', 'ç' => 'c',
            'â' => 'a', 'î' => 'i', 'é' => 'e', 'è' => 'e',
        ]);
    }
}
