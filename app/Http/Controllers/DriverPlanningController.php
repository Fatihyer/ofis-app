<?php

namespace App\Http\Controllers;

use App\Models\DriverPlanningProfile;
use App\Models\Acente;
use App\Models\Firma;
use App\Models\Option;
use App\Models\Transfer;
use App\Models\Vehicule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class DriverPlanningController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('permission:vehicules.view')->only(['index']);
        $this->middleware('permission:vehicules.update')->only(['update']);
    }

    public function index(Request $request)
    {
        $employmentTypes = DriverPlanningProfile::employmentTypes();
        $vehicules = Vehicule::query()
            ->whereNull('sales')
            ->orderBy('name')
            ->get(['id', 'name', 'plaka', 'capacity']);
        $firmas = Firma::orderBy('name')->pluck('name', 'id');
        $configuredDriverIds = $this->configuredDriverIds();
        $usedDriverIds = Transfer::query()
            ->whereNotNull('driver_id')
            ->pluck('driver_id')
            ->merge(Transfer::query()->whereNotNull('second_driver_id')->pluck('second_driver_id'))
            ->filter()
            ->unique()
            ->values();

        $drivers = Acente::query()
            ->with(['firmas', 'driverPlanningProfile', 'driverVehicleCapabilities' => function ($query) {
                $query->orderBy('name');
            }])
            ->when($request->input('all') !== '1' && $configuredDriverIds->isNotEmpty(), function ($query) use ($configuredDriverIds) {
                $query->whereIn('id', $configuredDriverIds);
            })
            ->when($request->input('all') !== '1' && $configuredDriverIds->isEmpty() && !$request->filled('firma'), function ($query) use ($usedDriverIds) {
                $query->where(function ($inner) use ($usedDriverIds) {
                    $inner->whereIn('id', $usedDriverIds)
                        ->orWhereHas('driverPlanningProfile')
                        ->orWhereHas('firmas', function ($firma) {
                            $firma->where('name', 'like', '%chauff%')
                                ->orWhere('name', 'like', '%driver%')
                                ->orWhere('name', 'like', '%şof%')
                                ->orWhere('name', 'like', '%sof%');
                        });
                });
            })
            ->when($request->filled('firma'), function ($query) use ($request) {
                $query->whereHas('firmas', function ($firma) use ($request) {
                    $firma->where('firmas.id', (int) $request->input('firma'));
                });
            })
            ->when($request->filled('employment_type'), function ($query) use ($request) {
                $query->whereHas('driverPlanningProfile', function ($profile) use ($request) {
                    $profile->where('employment_type', $request->input('employment_type'));
                });
            })
            ->when($request->filled('vehicule_id'), function ($query) use ($request) {
                $query->whereHas('driverVehicleCapabilities', function ($vehicle) use ($request) {
                    $vehicle->where('vehicules.id', (int) $request->input('vehicule_id'));
                });
            })
            ->when($request->input('priority') === '1', function ($query) {
                $query->whereHas('driverPlanningProfile', function ($profile) {
                    $profile->where('is_priority', true);
                });
            })
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = '%' . trim((string) $request->input('search')) . '%';
                $query->where(function ($inner) use ($search) {
                    $inner->where('name', 'like', $search)
                        ->orWhere('tel', 'like', $search)
                        ->orWhere('tittle', 'like', $search);
                });
            })
            ->get()
            ->sortBy(function (Acente $driver) {
                $profile = $driver->driverPlanningProfile;

                return [
                    $profile && $profile->is_priority ? 0 : 1,
                    $profile ? $profile->priority_level : 9,
                    $driver->name,
                ];
            })
            ->values();

        return view('driver_planning.index', compact('drivers', 'vehicules', 'employmentTypes', 'firmas', 'configuredDriverIds'));
    }

    public function update(Request $request, Acente $driver)
    {
        $data = $request->validate([
            'employment_type' => ['required', Rule::in(array_keys(DriverPlanningProfile::employmentTypes()))],
            'priority_level' => ['required', 'integer', 'min:1', 'max:9'],
            'is_priority' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'vehicules' => ['nullable', 'array'],
            'vehicules.*' => ['integer', 'exists:vehicules,id'],
            'preferred_vehicules' => ['nullable', 'array'],
            'preferred_vehicules.*' => ['integer', 'exists:vehicules,id'],
        ]);

        DB::transaction(function () use ($driver, $data) {
            $driver->driverPlanningProfile()->updateOrCreate(
                ['acente_id' => $driver->id],
                [
                    'employment_type' => $data['employment_type'],
                    'priority_level' => $data['priority_level'],
                    'is_priority' => (bool) ($data['is_priority'] ?? false),
                    'notes' => $data['notes'] ?? null,
                ]
            );

            $preferredVehicles = collect($data['preferred_vehicules'] ?? [])->map(function ($id) {
                return (int) $id;
            })->unique();
            $selectedVehicles = collect($data['vehicules'] ?? [])
                ->map(function ($id) {
                    return (int) $id;
                })
                ->merge($preferredVehicles)
                ->unique();

            $sync = $selectedVehicles->mapWithKeys(function (int $vehicleId) use ($preferredVehicles) {
                return [$vehicleId => ['preferred' => $preferredVehicles->contains($vehicleId)]];
            })->all();

            $driver->driverVehicleCapabilities()->sync($sync);
        });

        return redirect()
            ->route('driver-planning.index', $request->query())
            ->with('flash_message', 'Profil planning chauffeur mis à jour.');
    }

    private function configuredDriverIds()
    {
        $value = (string) Option::query()
            ->where('name', 'driverIds')
            ->value('value');

        return collect(preg_split('/[\s,;]+/', $value))
            ->filter()
            ->map(function ($id) {
                return (int) $id;
            })
            ->filter(function ($id) {
                return $id > 0;
            })
            ->unique()
            ->values();
    }
}
