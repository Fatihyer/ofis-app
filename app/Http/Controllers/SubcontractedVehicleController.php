<?php

namespace App\Http\Controllers;

use App\Models\Acente;
use App\Models\Hareket;
use App\Models\Option;
use App\Models\Transfer;
use Carbon\Carbon;
use Illuminate\Http\Request;

class SubcontractedVehicleController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware(function ($request, $next) {
            $user = auth()->user();
            if ($user && (
                $user->hasAnyRole(['Superadmin', 'Admin', 'Transport', 'Comptabilité', 'Compta'])
                || $user->hasAnyPermission(['transfers.view', 'balances.view', 'acentes.view'])
            )) {
                return $next($request);
            }

            abort(403);
        });
    }

    public function index(Request $request)
    {
        $start = $request->filled('start_date')
            ? Carbon::parse($request->input('start_date'))->startOfDay()
            : now()->startOfMonth();
        $end = $request->filled('end_date')
            ? Carbon::parse($request->input('end_date'))->endOfDay()
            : now()->addDays(45)->endOfDay();

        if ($end->lt($start)) {
            $end = $start->copy()->addDays(45)->endOfDay();
        }

        $providerId = (int) $request->input('provider_id', 0);
        $status = $request->input('status', 'all');
        $search = trim((string) $request->input('q', ''));

        $congeIds = collect(explode(',', (string) Option::where('name', 'conge')->value('value')))
            ->map(fn ($id) => (int) trim($id))
            ->filter()
            ->values()
            ->all();

        $query = Transfer::with([
                'post.acente:id,name,color',
                'externalVehicleProvider:id,name',
                'vehicule:id,name,plaka,real,sales',
                'driver:id,name',
                'servicetype:id,name',
                'status:id,name',
            ])
            ->whereNull('deleted_at')
            ->when(!empty($congeIds), fn ($q) => $q->whereNotIn('servicetype_id', $congeIds))
            ->whereBetween('start_date', [$start, $end])
            ->where(function ($q) {
                $q->whereNotNull('vehicle_provider_acente_id')
                    ->orWhereNotNull('external_vehicle_price')
                    ->orWhereRaw("TRIM(COALESCE(external_vehicle_note, '')) != ''");
            });

        if ($providerId > 0) {
            $query->where('vehicle_provider_acente_id', $providerId);
        }

        if ($status === 'missing_provider') {
            $query->whereNull('vehicle_provider_acente_id');
        } elseif ($status === 'missing_price') {
            $query->where(function ($q) {
                $q->whereNull('external_vehicle_price')
                    ->orWhere('external_vehicle_price', '<=', 0);
            });
        } elseif ($status === 'ready') {
            $query->whereNotNull('vehicle_provider_acente_id')
                ->where('external_vehicle_price', '>', 0);
        }

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('id', $search)
                    ->orWhere('post_id', $search)
                    ->orWhere('from', 'like', '%' . $search . '%')
                    ->orWhere('target', 'like', '%' . $search . '%')
                    ->orWhere('external_vehicle_note', 'like', '%' . $search . '%')
                    ->orWhereHas('post.acente', fn ($sub) => $sub->where('name', 'like', '%' . $search . '%'))
                    ->orWhereHas('externalVehicleProvider', fn ($sub) => $sub->where('name', 'like', '%' . $search . '%'));
            });
        }

        $transfers = $query->orderBy('start_date')->paginate(100)->appends($request->query());
        $movementMap = Hareket::where('hareketable_type', 'external_vehicle_transfer')
            ->whereIn('hareketable_id', $transfers->getCollection()->pluck('id'))
            ->whereNull('deleted_at')
            ->get()
            ->keyBy('hareketable_id');

        $summaryQuery = Transfer::query()
            ->whereNull('deleted_at')
            ->when(!empty($congeIds), fn ($q) => $q->whereNotIn('servicetype_id', $congeIds))
            ->whereBetween('start_date', [$start, $end])
            ->where(function ($q) {
                $q->whereNotNull('vehicle_provider_acente_id')
                    ->orWhereNotNull('external_vehicle_price')
                    ->orWhereRaw("TRIM(COALESCE(external_vehicle_note, '')) != ''");
            });

        $summaryRows = (clone $summaryQuery)->get(['id', 'vehicle_provider_acente_id', 'external_vehicle_price']);
        $providerIds = $summaryRows->pluck('vehicle_provider_acente_id')->filter()->unique()->values();
        $providerNames = Acente::whereIn('id', $providerIds)->orderBy('name')->pluck('name', 'id');
        $providerOptions = Acente::whereIn('id', $providerIds)->orderBy('name')->pluck('name', 'id');

        $providerSummaries = $summaryRows
            ->groupBy(fn ($transfer) => $transfer->vehicle_provider_acente_id ?: 0)
            ->map(function ($rows, $id) use ($providerNames) {
                return (object) [
                    'id' => (int) $id,
                    'name' => (int) $id > 0 ? ($providerNames[(int) $id] ?? 'Prestataire #' . $id) : 'Fournisseur manquant',
                    'count' => $rows->count(),
                    'amount' => $rows->sum(fn ($row) => (float) $row->external_vehicle_price),
                    'missing_price' => $rows->filter(fn ($row) => (float) $row->external_vehicle_price <= 0)->count(),
                ];
            })
            ->sortByDesc('amount')
            ->values();

        $stats = [
            'total' => $summaryRows->count(),
            'amount' => $summaryRows->sum(fn ($row) => (float) $row->external_vehicle_price),
            'providers' => $providerIds->count(),
            'missing_provider' => $summaryRows->whereNull('vehicle_provider_acente_id')->count(),
            'missing_price' => $summaryRows->filter(fn ($row) => (float) $row->external_vehicle_price <= 0)->count(),
        ];

        return view('vehicules.subcontracted', compact(
            'transfers',
            'start',
            'end',
            'providerId',
            'status',
            'search',
            'providerOptions',
            'providerSummaries',
            'movementMap',
            'stats'
        ));
    }

    public function syncMovement(Request $request, Transfer $transfer)
    {
        $transfer->load(['post', 'externalVehicleProvider', 'vehicule']);

        if (!$transfer->vehicle_provider_acente_id) {
            return back()->withErrors(['vehicle_provider_acente_id' => 'Renseignez le fournisseur véhicule avant de créer le mouvement.']);
        }

        if ((float) $transfer->external_vehicle_price <= 0) {
            return back()->withErrors(['external_vehicle_price' => 'Renseignez le prix fournisseur avant de créer le mouvement.']);
        }

        $vehicleLabel = optional($transfer->vehicule)->plaka
            ?: optional($transfer->vehicule)->name
            ?: 'véhicule extérieur';

        $movement = Hareket::firstOrNew([
            'hareketable_type' => 'external_vehicle_transfer',
            'hareketable_id' => $transfer->id,
        ]);

        $movement->fill([
            'sirket_id' => null,
            'aciklama' => 'Véhicule sous-traité - transfert #' . $transfer->id . ' - ' . $vehicleLabel,
            'amount' => abs((float) $transfer->external_vehicle_price),
            'tarih' => $transfer->start_date ?: now(),
            'ab' => 2,
            'kur_id' => 1,
            'acente_id' => $transfer->vehicle_provider_acente_id,
            'post_id' => $transfer->post_id ?: 0,
        ]);
        $movement->save();

        return back()->with('flash_message', 'Mouvement fournisseur véhicule créé / mis à jour.');
    }
}
