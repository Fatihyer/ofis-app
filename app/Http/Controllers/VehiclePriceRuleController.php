<?php

namespace App\Http\Controllers;

use App\Models\VehiclePriceRule;
use App\Exports\VehiclePriceRulesExport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class VehiclePriceRuleController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('permission:rates.manage');
    }

    public function index()
    {
        $rules = VehiclePriceRule::orderBy('vehicle_type')
            ->orderBy('service_type')
            ->get();

        $dateAdjustments = DB::table('vehicle_price_date_adjustments')
            ->orderByDesc('start_date')
            ->orderBy('vehicle_type')
            ->get();

        $vehicleTypes = [
            'SEDAN_4',
            'CLASS_V',
            'VAN_8',
            'SPRINTER_19',
            'MINIBUS_30',
            'COACH_45',
            'COACH_50',
            'COACH_55',
            'COACH_60',
        ];

        $serviceTypes = ['transfer', 'dispo'];

        return view('vehicle_price_rules.index', compact('rules', 'vehicleTypes', 'serviceTypes', 'dateAdjustments'));
    }

    public function export()
    {
        return Excel::download(
            new VehiclePriceRulesExport(),
            'tarifs-vehicules-' . now('Europe/Paris')->format('Ymd-His') . '.xlsx'
        );
    }

    public function store(Request $request)
    {
        $data = $this->validatedData($request);
        VehiclePriceRule::create($data);

        return redirect()
            ->route('vehicle-price-rules.index')
            ->with('success', 'Le tarif véhicule a été ajouté.');
    }

    public function update(Request $request, VehiclePriceRule $vehiclePriceRule)
    {
        $data = $this->validatedData($request);
        $vehiclePriceRule->update($data);

        return redirect()
            ->route('vehicle-price-rules.index')
            ->with('success', 'Le tarif véhicule a été mis à jour.');
    }

    public function destroy(VehiclePriceRule $vehiclePriceRule)
    {
        $vehiclePriceRule->delete();

        return redirect()
            ->route('vehicle-price-rules.index')
            ->with('success', 'Le tarif véhicule a été supprimé.');
    }

    public function storeDateAdjustment(Request $request)
    {
        DB::table('vehicle_price_date_adjustments')->insert($this->validatedDateAdjustment($request) + [
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return redirect()
            ->route('vehicle-price-rules.index')
            ->with('success', 'La règle par dates a été ajoutée.');
    }

    public function updateDateAdjustment(Request $request, int $adjustment)
    {
        DB::table('vehicle_price_date_adjustments')
            ->where('id', $adjustment)
            ->update($this->validatedDateAdjustment($request) + [
                'updated_at' => now(),
            ]);

        return redirect()
            ->route('vehicle-price-rules.index')
            ->with('success', 'La règle par dates a été mise à jour.');
    }

    public function destroyDateAdjustment(int $adjustment)
    {
        DB::table('vehicle_price_date_adjustments')->where('id', $adjustment)->delete();

        return redirect()
            ->route('vehicle-price-rules.index')
            ->with('success', 'La règle par dates a été supprimée.');
    }

    private function validatedData(Request $request): array
    {
        return $request->validate([
            'vehicle_type' => 'required|string|max:100',
            'service_type' => 'nullable|string|max:100',
            'base_rate' => 'required|numeric|min:0',
            'included_km' => 'required|integer|min:0',
            'included_hours' => 'required|numeric|min:0',
            'extra_km_rate' => 'required|numeric|min:0',
            'extra_hour_rate' => 'required|numeric|min:0',
            'night_extra_hour_rate' => 'required|numeric|min:0',
            'minimum_charge' => 'required|numeric|min:0',
            'driver_meal_cost' => 'required|numeric|min:0',
            'driver_hotel_cost' => 'required|numeric|min:0',
            'default_margin_percent' => 'required|numeric|min:0|max:100',
            'vat_rate' => 'required|numeric|min:0|max:100',
            'active' => 'nullable|boolean',
        ]) + ['active' => 0];
    }

    private function validatedDateAdjustment(Request $request): array
    {
        $data = $request->validate([
            'label' => 'required|string|max:255',
            'vehicle_type' => 'nullable|string|max:100',
            'service_type' => 'nullable|string|max:100',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'adjustment_type' => 'required|in:percent,fixed',
            'direction' => 'required|in:increase,discount',
            'adjustment_value' => 'required|numeric|min:0',
            'active' => 'nullable|boolean',
        ]);

        $data['vehicle_type'] = $data['vehicle_type'] ?: null;
        $data['service_type'] = $data['service_type'] ?: null;
        $data['active'] = (int) ($data['active'] ?? 0);

        return $data;
    }
}
