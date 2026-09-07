<?php

namespace App\Http\Controllers;

use App\Models\Stock;
use App\Models\Hareket;
use App\Models\Acente;
use App\Models\Kur;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Helpers\HareketHelper;

class StockController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth']);
    }

    /* =========================
       INDEX
    ==========================*/
    public function index(Request $request)
    {
        $bulkProductIds = $this->stockOptionIds('stock_bulk_product_ids', [146, 881]);
        $onDemandProductIds = $this->stockOptionIds('stock_on_demand_product_ids', [145]);
        $managedProductIds = array_values(array_unique(array_merge($bulkProductIds, $onDemandProductIds)));
        $type = $request->input('type');
        $productId = $request->input('product_id');
        $movementType = $request->input('movement_type');
        $clientAgencyId = $request->input('client_agency_id');

        $query = Stock::with(['urun', 'aAcente', 'bAcente', 'harekets'])
            ->when($type === 'bulk', fn ($q) => $q->whereIn('urun_id', $bulkProductIds))
            ->when($type === 'ondemand', fn ($q) => $q->whereIn('urun_id', $onDemandProductIds))
            ->when($productId, fn ($q) => $q->where('urun_id', $productId))
            ->when($clientAgencyId, fn ($q) => $q->where('b_acente_id', $clientAgencyId))
            ->when(in_array($movementType, ['in', 'out', 'adjust'], true), fn ($q) => $q->where('movement_type', $movementType))
            ->orderBy('tarih', 'desc')
            ->orderBy('id', 'desc');

        $movementQuantityTotal = (int) (clone $query)->sum('adet');
        $stocks = $query->paginate(50)->appends($request->query());

        $productStats = Stock::query()
            ->with('urun')
            ->selectRaw("urun_id,
                COUNT(*) as movement_count,
                COALESCE(SUM(adet),0) as total_qty,
                COALESCE(SUM(CASE WHEN affects_stock = 1 AND movement_type = 'in' THEN adet ELSE 0 END),0) as stock_in,
                COALESCE(SUM(CASE WHEN affects_stock = 1 AND movement_type = 'out' THEN adet ELSE 0 END),0) as stock_out,
                COALESCE(SUM(CASE WHEN affects_stock = 1 AND movement_type = 'adjust' THEN adet ELSE 0 END),0) as stock_adjust,
                COALESCE(SUM(buy_price),0) as total_buy,
                COALESCE(SUM(sell_price),0) as total_sell")
            ->when(!$productId && !empty($managedProductIds), fn ($q) => $q->whereIn('urun_id', $managedProductIds))
            ->when($type === 'bulk', fn ($q) => $q->whereIn('urun_id', $bulkProductIds))
            ->when($type === 'ondemand', fn ($q) => $q->whereIn('urun_id', $onDemandProductIds))
            ->when($productId, fn ($q) => $q->where('urun_id', $productId))
            ->when($clientAgencyId, fn ($q) => $q->where('b_acente_id', $clientAgencyId))
            ->when(in_array($movementType, ['in', 'out', 'adjust'], true), fn ($q) => $q->where('movement_type', $movementType))
            ->groupBy('urun_id')
            ->orderByDesc('total_qty')
            ->get()
            ->map(function ($row) use ($bulkProductIds, $onDemandProductIds) {
                $row->mode = in_array((int) $row->urun_id, $bulkProductIds, true) ? 'bulk' : (in_array((int) $row->urun_id, $onDemandProductIds, true) ? 'ondemand' : 'other');
                $row->stock_remaining = (int) $row->stock_in + (int) $row->stock_adjust - (int) $row->stock_out;
                $row->margin = (float) $row->total_sell - (float) $row->total_buy;
                return $row;
            });

        $stockTotals = Stock::query()
            ->where('affects_stock', 1)
            ->whereIn('urun_id', $bulkProductIds)
            ->selectRaw("COALESCE(SUM(CASE WHEN movement_type = 'in' THEN adet ELSE 0 END),0) as stock_in,
                COALESCE(SUM(CASE WHEN movement_type = 'out' THEN adet ELSE 0 END),0) as stock_out,
                COALESCE(SUM(CASE WHEN movement_type = 'adjust' THEN adet ELSE 0 END),0) as stock_adjust")
            ->first();

        $summary = [
            'movement_count' => Stock::count(),
            'total_qty' => (int) Stock::sum('adet'),
            'stock_in' => (int) ($stockTotals->stock_in ?? 0),
            'stock_out' => (int) ($stockTotals->stock_out ?? 0),
            'stock_adjust' => (int) ($stockTotals->stock_adjust ?? 0),
            'total_buy' => (float) Stock::selectRaw('COALESCE(SUM(buy_price),0) as total')->value('total'),
            'total_sell' => (float) Stock::selectRaw('COALESCE(SUM(sell_price),0) as total')->value('total'),
        ];
        $summary['stock_remaining'] = $summary['stock_in'] + $summary['stock_adjust'] - $summary['stock_out'];
        $summary['margin'] = $summary['total_sell'] - $summary['total_buy'];

        $movementProductIds = Stock::query()
            ->whereNotNull('urun_id')
            ->distinct()
            ->pluck('urun_id')
            ->filter()
            ->values()
            ->all();

        $products = Acente::whereIn('id', array_unique(array_merge($managedProductIds, $movementProductIds)))
            ->orderBy('name')
            ->pluck('name', 'id');

        $clientAgencyIds = Stock::query()
            ->whereNotNull('b_acente_id')
            ->distinct()
            ->pluck('b_acente_id')
            ->filter()
            ->values();

        $clientAgencies = Acente::whereIn('id', $clientAgencyIds)
            ->orderBy('name')
            ->pluck('name', 'id');

        return view('stocks.index', compact('stocks', 'productStats', 'summary', 'movementQuantityTotal', 'bulkProductIds', 'onDemandProductIds', 'products', 'clientAgencies', 'type', 'productId', 'movementType', 'clientAgencyId'));
    }

    public function export(Request $request)
    {
        $bulkProductIds = $this->stockOptionIds('stock_bulk_product_ids', [146, 881]);
        $onDemandProductIds = $this->stockOptionIds('stock_on_demand_product_ids', [145]);
        $type = $request->input('type');
        $productId = $request->input('product_id');
        $movementType = $request->input('movement_type');
        $clientAgencyId = $request->input('client_agency_id');

        $query = Stock::with(['urun', 'aAcente', 'bAcente'])
            ->when($type === 'bulk', fn ($q) => $q->whereIn('urun_id', $bulkProductIds))
            ->when($type === 'ondemand', fn ($q) => $q->whereIn('urun_id', $onDemandProductIds))
            ->when($productId, fn ($q) => $q->where('urun_id', $productId))
            ->when($clientAgencyId, fn ($q) => $q->where('b_acente_id', $clientAgencyId))
            ->when(in_array($movementType, ['in', 'out', 'adjust'], true), fn ($q) => $q->where('movement_type', $movementType))
            ->orderBy('tarih', 'desc')
            ->orderBy('id', 'desc');

        $quantityTotal = (int) (clone $query)->sum('adet');
        $filename = 'stocks_' . now()->format('Ymd_His') . '.csv';

        return response()->streamDownload(function () use ($query, $bulkProductIds, $onDemandProductIds, $quantityTotal) {
            $handle = fopen('php://output', 'w');
            fwrite($handle, "\xEF\xBB\xBF");

            fputcsv($handle, [
                '#',
                'Date',
                'Produit',
                'Mouvement',
                'Mode',
                'Quantité',
                'Stock',
                'Fournisseur',
                'Client / agence',
                'Achat',
                'Vente',
                'Marge',
                'Dossier',
                'Description',
            ], ';');

            $query->chunk(500, function ($stocks) use ($handle, $bulkProductIds, $onDemandProductIds) {
                foreach ($stocks as $stock) {
                    $buyTotal = (float) $stock->buy_price;
                    $sellTotal = (float) $stock->sell_price;
                    $margin = $sellTotal - $buyTotal;

                    fputcsv($handle, [
                        $stock->id,
                        $stock->tarih ? \Carbon\Carbon::parse($stock->tarih)->format('d/m/Y') : '',
                        optional($stock->urun)->name ?: '',
                        $this->movementExportLabel($stock->movement_type ?? 'out'),
                        $this->modeExportLabel((int) $stock->urun_id, $bulkProductIds, $onDemandProductIds),
                        (int) $stock->adet,
                        $stock->affects_stock ? 'Oui' : 'Non',
                        optional($stock->aAcente)->name ?: '',
                        optional($stock->bAcente)->name ?: '',
                        $this->exportMoney($buyTotal),
                        $this->exportMoney($sellTotal),
                        $this->exportMoney($margin),
                        $stock->post_id ?: '',
                        $stock->aciklama ?: '',
                    ], ';');
                }
            });

            fwrite($handle, "\n");
            fputcsv($handle, ['Quantité totale', $quantityTotal], ';');
            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    private function stockOptionIds(string $name, array $default): array
    {
        $value = DB::table('options')->where('name', $name)->value('value');
        if (!$value) {
            return $default;
        }

        $ids = collect(explode(',', (string) $value))
            ->map(fn ($id) => (int) trim($id))
            ->filter()
            ->values()
            ->all();

        return empty($ids) ? $default : $ids;
    }

    private function movementExportLabel(string $type): string
    {
        return match ($type) {
            'in' => 'Entrée',
            'adjust' => 'Ajustement',
            default => 'Sortie',
        };
    }

    private function modeExportLabel(int $productId, array $bulkProductIds, array $onDemandProductIds): string
    {
        if (in_array($productId, $bulkProductIds, true)) {
            return 'Stock suivi';
        }

        if (in_array($productId, $onDemandProductIds, true)) {
            return 'À la demande';
        }

        return 'Autre';
    }

    private function exportMoney(float $amount): string
    {
        return number_format($amount, 2, ',', '');
    }

    /* =========================
       CREATE
    ==========================*/
    public function create(Request $request)
    {
        $acentes = Acente::orderBy('name')->pluck('name', 'id');
        $uruns   = Acente::orderBy('name')->pluck('name', 'id'); // ürün = acente
        $kurs    = Kur::orderBy('name')->pluck('name', 'id');

        $file   = $request->post_id;
        $acente = $request->acente_id;
        $bulkProductIds = $this->stockOptionIds('stock_bulk_product_ids', [146, 881]);
        $onDemandProductIds = $this->stockOptionIds('stock_on_demand_product_ids', [145]);

        return view('stocks.create', compact(
            'acentes',
            'uruns',
            'kurs',
            'file',
            'acente',
            'bulkProductIds',
            'onDemandProductIds'
        ));
    }

    /* =========================
       STORE
    ==========================*/
   public function store(Request $request)
{
    $data = $this->validatedStockData($request);

    DB::transaction(function () use ($request, $data) {
        $stock = Stock::create($data + [
            'ab' => $data['movement_type'] === 'out' ? 1 : 2,
        ]);

        $this->recreateFinancialMovements($stock, $request);
    });

    return redirect()
        ->route('stocks.index')
        ->with('flash_message', 'Mouvement de stock enregistré');
}

    /* =========================
       EDIT
    ==========================*/
    public function edit(Stock $stock)
    {
        $stock->load('harekets');

        $acentes = Acente::orderBy('name')->pluck('name', 'id');
        $uruns   = Acente::orderBy('name')->pluck('name', 'id');
        $kurs    = Kur::orderBy('name')->pluck('name', 'id');
        $bulkProductIds = $this->stockOptionIds('stock_bulk_product_ids', [146, 881]);
        $onDemandProductIds = $this->stockOptionIds('stock_on_demand_product_ids', [145]);

        return view('stocks.edit', compact('stock', 'acentes', 'uruns', 'kurs', 'bulkProductIds', 'onDemandProductIds'));
    }

    /* =========================
       UPDATE
    ==========================*/
    public function update(Request $request, Stock $stock)
{
    $data = $this->validatedStockData($request);

    DB::transaction(function () use ($request, $stock, $data) {
        $stock->update($data + [
            'ab' => $data['movement_type'] === 'out' ? 1 : 2,
        ]);

        $this->recreateFinancialMovements($stock, $request);
    });

    return redirect()->route('stocks.index')
        ->with('flash_message', 'Mouvement de stock mis à jour');
}

    private function validatedStockData(Request $request): array
    {
        $request->validate([
            'tarih' => 'required|date',
            'urun_id' => 'required|integer',
            'adet' => 'required|integer|min:1',
            'movement_type' => 'required|in:in,out,adjust',
            'a_acente_id' => 'required|different:b_acente_id',
            'b_acente_id' => 'required',
            'buy_price' => 'required|numeric|min:0',
            'sell_price' => 'required|numeric|min:0',
            'kur_id' => 'required',
        ]);

        $onDemandProductIds = $this->stockOptionIds('stock_on_demand_product_ids', [145]);
        $affectsStock = $request->boolean('affects_stock') && !in_array((int) $request->urun_id, $onDemandProductIds, true);

        return [
            'tarih' => $request->tarih,
            'post_id' => $request->post_id,
            'urun_id' => $request->urun_id,
            'adet' => $request->adet,
            'movement_type' => $request->movement_type,
            'affects_stock' => $affectsStock ? 1 : 0,
            'a_acente_id' => $request->a_acente_id,
            'b_acente_id' => $request->b_acente_id,
            'aciklama' => $request->aciklama,
            'buy_price' => $request->buy_price,
            'sell_price' => $request->sell_price,
            'credit' => $request->input('credit', 0),
        ];
    }

    private function recreateFinancialMovements(Stock $stock, Request $request): void
    {
        $stock->harekets()->delete();

        if ($stock->movement_type === 'adjust') {
            return;
        }

        if ((float) $request->buy_price > 0) {
            HareketHelper::create($stock, [
                'aciklama' => 'Achat billet' . (!empty($request->aciklama) ? ' - '.$request->aciklama : ''),
                'tarih' => $request->tarih,
                'post_id' => $request->post_id,
                'amount' => $request->buy_price,
                'ab' => 2,
                'kur_id' => $request->kur_id,
                'acente_id' => $request->a_acente_id,
                'urun_id' => $request->urun_id,
                'adet' => $request->adet,
            ]);
        }

        if ($stock->movement_type !== 'out') {
            return;
        }

        if ($request->sell_price > $request->buy_price) {
            HareketHelper::create($stock, [
                'aciklama' => 'Marge billet' . (!empty($request->aciklama) ? ' - '.$request->aciklama : ''),
                'tarih' => $request->tarih,
                'post_id' => $request->post_id,
                'amount' => $request->sell_price - $request->buy_price,
                'ab' => 2,
                'kur_id' => $request->kur_id,
                'acente_id' => $request->urun_id,
                'urun_id' => $request->a_acente_id,
                'adet' => $request->adet,
            ]);
        }

        if ((float) $request->sell_price > 0) {
            HareketHelper::create($stock, [
                'aciklama' => 'Vente billet' . (!empty($request->aciklama) ? ' - '.$request->aciklama : ''),
                'tarih' => $request->tarih,
                'post_id' => $request->post_id,
                'amount' => $request->sell_price,
                'ab' => 1,
                'kur_id' => $request->kur_id,
                'acente_id' => $request->b_acente_id,
                'urun_id' => $request->urun_id,
                'adet' => $request->adet,
            ]);
        }
    }

    /* =========================
       DELETE
    ==========================*/
    public function destroy(Stock $stock)
    {
        DB::transaction(function () use ($stock) {
            $stock->harekets()->delete();
            $stock->delete();
        });

        return redirect()
            ->route('stocks.index')
            ->with('flash_message', 'Mouvement de stock supprimé');
    }
}
