<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\VehicleMaintenance;
use App\Models\Vehicule;
use App\Models\Acente;
use App\Models\Payment;
use App\Models\Kur;
use App\Models\Hareket;
use Auth;
use Illuminate\Support\Facades\DB;

class VehicleMaintenanceController extends Controller
{
    private array $categories = [
        'entretien' => 'Entretien',
        'controle_technique' => 'Contrôle technique',
        'carrosserie' => 'Carrosserie',
        'garage' => 'Garage',
        'pneus' => 'Pneus',
        'assurance' => 'Assurance',
        'reparation' => 'Réparation',
        'lavage' => 'Lavage',
        'autre' => 'Autre',
    ];

    public function __construct() {
          $this->middleware(['role:Admin|ofis|transport']);
      }

    public function index(Request $request)
    {
        $startDate = $request->input('start_date', now('Europe/Paris')->startOfMonth()->toDateString());
        $endDate = $request->input('end_date', now('Europe/Paris')->endOfMonth()->toDateString());
        $vehiculeId = $request->input('vehicule_id');
        $category = $request->input('category');

        $maintenances = VehicleMaintenance::with(['vehicule', 'acente', 'payment', 'kur', 'hareket'])
            ->whereBetween('service_date', [$startDate, $endDate])
            ->when($vehiculeId, fn ($q) => $q->where('vehicule_id', $vehiculeId))
            ->when($category, fn ($q) => $q->where('category', $category))
            ->orderBy('service_date', 'desc')
            ->get();

        $vehicules = Vehicule::orderBy('name')->get();
        $categories = $this->categories;
        $totalAmount = $maintenances->sum('amount');

        return view('vehicle_maintenance.index', compact('maintenances', 'vehicules', 'categories', 'startDate', 'endDate', 'vehiculeId', 'category', 'totalAmount'));
    }

    public function create()
    {
        return view('vehicle_maintenance.create', $this->formData());
    }

    public function store(Request $request)
    {
        $data = $this->validatedData($request);

        DB::transaction(function () use ($data) {
            $maintenance = VehicleMaintenance::create($data);
            $this->syncHareket($maintenance);
        });

        return redirect()->route('vehicle_maintenance.index')->with('success', 'Frais véhicule enregistré.');
    }

    public function show($id)
    {
        $maintenance = VehicleMaintenance::with(['vehicule', 'acente', 'payment', 'kur', 'hareket'])->findOrFail($id);
        return view('vehicle_maintenance.show', compact('maintenance'));
    }

    public function edit($id)
    {
        $maintenance = VehicleMaintenance::findOrFail($id);
        return view('vehicle_maintenance.edit', $this->formData(['maintenance' => $maintenance]));
    }

    public function update(Request $request, $id)
    {
        $data = $this->validatedData($request);

        DB::transaction(function () use ($data, $id) {
            $maintenance = VehicleMaintenance::findOrFail($id);
            $maintenance->update($data);
            $this->syncHareket($maintenance);
        });

        return redirect()->route('vehicle_maintenance.index')->with('success', 'Frais véhicule mis à jour.');
    }

    public function monthlyCosts(Request $request)
    {
        $month = $request->input('month', now('Europe/Paris')->format('Y-m'));
        $startDate = \Carbon\Carbon::parse($month . '-01', 'Europe/Paris')->startOfMonth()->toDateString();
        $endDate = \Carbon\Carbon::parse($month . '-01', 'Europe/Paris')->endOfMonth()->toDateString();

        $categories = $this->categories;

        $fuel = DB::table('fuel_purchases')
            ->select('vehicule_id', DB::raw('SUM(amount) as fuel_amount'), DB::raw('SUM(volume) as fuel_liters'))
            ->whereBetween('purchase_date', [$startDate, $endDate . ' 23:59:59'])
            ->groupBy('vehicule_id');

        $maintenance = DB::table('vehicle_maintenances')
            ->select('vehicule_id',
                DB::raw('SUM(amount) as maintenance_amount'),
                DB::raw("SUM(CASE WHEN category = 'entretien' THEN amount ELSE 0 END) as entretien"),
                DB::raw("SUM(CASE WHEN category = 'controle_technique' THEN amount ELSE 0 END) as controle_technique"),
                DB::raw("SUM(CASE WHEN category = 'carrosserie' THEN amount ELSE 0 END) as carrosserie"),
                DB::raw("SUM(CASE WHEN category = 'garage' THEN amount ELSE 0 END) as garage"),
                DB::raw("SUM(CASE WHEN category = 'pneus' THEN amount ELSE 0 END) as pneus"),
                DB::raw("SUM(CASE WHEN category = 'assurance' THEN amount ELSE 0 END) as assurance"),
                DB::raw("SUM(CASE WHEN category = 'reparation' THEN amount ELSE 0 END) as reparation"),
                DB::raw("SUM(CASE WHEN category = 'lavage' THEN amount ELSE 0 END) as lavage"),
                DB::raw("SUM(CASE WHEN category IS NULL OR category = 'autre' THEN amount ELSE 0 END) as autre")
            )
            ->whereBetween('service_date', [$startDate, $endDate])
            ->groupBy('vehicule_id');

        $hermes = DB::table('hermes_daily_stats')
            ->select('vehicule_id', DB::raw('SUM(distance_km) as hermes_km'), DB::raw('SUM(duration_sec) as hermes_duration_sec'))
            ->whereBetween('day', [$startDate, $endDate])
            ->groupBy('vehicule_id');

        $planning = DB::table('transfers')
            ->select('vehicule_id', DB::raw('SUM(km) as planning_km'), DB::raw('COUNT(*) as transfer_count'))
            ->whereNull('deleted_at')
            ->whereBetween('start_date', [$startDate, $endDate . ' 23:59:59'])
            ->groupBy('vehicule_id');

        $rows = DB::table('vehicules as v')
            ->leftJoinSub($fuel, 'fuel', 'fuel.vehicule_id', '=', 'v.id')
            ->leftJoinSub($maintenance, 'm', 'm.vehicule_id', '=', 'v.id')
            ->leftJoinSub($hermes, 'h', 'h.vehicule_id', '=', 'v.id')
            ->leftJoinSub($planning, 'p', 'p.vehicule_id', '=', 'v.id')
            ->select('v.id', 'v.name', 'v.plaka', 'v.real',
                DB::raw('COALESCE(fuel.fuel_amount,0) as fuel_amount'),
                DB::raw('COALESCE(fuel.fuel_liters,0) as fuel_liters'),
                DB::raw('COALESCE(m.maintenance_amount,0) as maintenance_amount'),
                DB::raw('COALESCE(m.entretien,0) as entretien'),
                DB::raw('COALESCE(m.controle_technique,0) as controle_technique'),
                DB::raw('COALESCE(m.carrosserie,0) as carrosserie'),
                DB::raw('COALESCE(m.garage,0) as garage'),
                DB::raw('COALESCE(m.pneus,0) as pneus'),
                DB::raw('COALESCE(m.assurance,0) as assurance'),
                DB::raw('COALESCE(m.reparation,0) as reparation'),
                DB::raw('COALESCE(m.lavage,0) as lavage'),
                DB::raw('COALESCE(m.autre,0) as autre'),
                DB::raw('COALESCE(h.hermes_km,0) as hermes_km'),
                DB::raw('COALESCE(h.hermes_duration_sec,0) as hermes_duration_sec'),
                DB::raw('COALESCE(p.planning_km,0) as planning_km'),
                DB::raw('COALESCE(p.transfer_count,0) as transfer_count')
            )
            ->where(function ($q) {
                $q->whereNotNull('v.real')
                  ->orWhereNotNull('fuel.fuel_amount')
                  ->orWhereNotNull('m.maintenance_amount')
                  ->orWhereNotNull('h.hermes_km')
                  ->orWhereNotNull('p.planning_km');
            })
            ->orderBy('v.name')
            ->get();

        return view('vehicle_maintenance.monthly_costs', compact('rows', 'month', 'startDate', 'endDate', 'categories'));
    }

    public function destroy($id)
    {
        $currentUser = Auth::user();
        if (!$currentUser->hasRole('Superadmin')) {
            return redirect()->route('vehicle_maintenance.index')->with('error', 'Suppression réservée aux super administrateurs.');
        }

        DB::transaction(function () use ($id) {
            $maintenance = VehicleMaintenance::findOrFail($id);
            if ($maintenance->hareket_id) {
                Hareket::where('id', $maintenance->hareket_id)->delete();
            }
            $maintenance->harekets()->delete();
            $maintenance->delete();
        });

        return redirect()->route('vehicle_maintenance.index')->with('success', 'Frais véhicule supprimé.');
    }

    private function formData(array $extra = []): array
    {
        return array_merge([
            'vehicules' => Vehicule::orderBy('name')->get(),
            'acentes' => Acente::orderBy('name')->get(),
            'payments' => Payment::orderBy('name')->get(),
            'kurs' => Kur::orderBy('name')->get(),
            'categories' => $this->categories,
        ], $extra);
    }

    private function validatedData(Request $request): array
    {
        return $request->validate([
            'vehicule_id' => 'required|exists:vehicules,id',
            'category' => 'nullable|string|max:50',
            'acente_id' => 'nullable|exists:acentes,id',
            'payment_id' => 'nullable|exists:payments,id',
            'kur_id' => 'nullable|exists:kurs,id',
            'invoiceno' => 'nullable|string|max:100',
            'description' => 'required|string',
            'service_date' => 'required|date',
            'amount' => 'required|numeric|min:0',
        ]);
    }

    private function syncHareket(VehicleMaintenance $maintenance): void
    {
        if (!$maintenance->acente_id) {
            if ($maintenance->hareket_id) {
                Hareket::where('id', $maintenance->hareket_id)->delete();
                $maintenance->forceFill(['hareket_id' => null])->save();
            }
            return;
        }

        $vehiculeName = trim(($maintenance->vehicule?->plaka ? $maintenance->vehicule->plaka . ' - ' : '') . ($maintenance->vehicule?->name ?? 'Véhicule'));
        $categoryLabel = $this->categories[$maintenance->category] ?? 'Frais véhicule';

        $data = [
            'aciklama' => $categoryLabel . ' - ' . $vehiculeName . ' - ' . $maintenance->description,
            'amount' => abs((float) $maintenance->amount),
            'tarih' => $maintenance->service_date,
            'ab' => 2,
            'kur_id' => $maintenance->kur_id ?: 1,
            'acente_id' => $maintenance->acente_id,
            'post_id' => 0,
            'payment_id' => $maintenance->payment_id,
            'invoiceno' => $maintenance->invoiceno,
            'hareketable_id' => $maintenance->id,
            'hareketable_type' => VehicleMaintenance::class,
        ];

        $hareket = $maintenance->hareket_id
            ? Hareket::find($maintenance->hareket_id)
            : $maintenance->harekets()->first();

        if ($hareket) {
            $hareket->update($data);
        } else {
            $hareket = $maintenance->harekets()->create($data);
        }

        if ((int) $maintenance->hareket_id !== (int) $hareket->id) {
            $maintenance->forceFill(['hareket_id' => $hareket->id])->save();
        }
    }
}
