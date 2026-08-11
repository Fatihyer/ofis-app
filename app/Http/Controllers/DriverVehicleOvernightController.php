<?php

namespace App\Http\Controllers;

use App\Models\Acente;
use App\Models\DriverVehicleOvernight;
use App\Models\Transfer;
use App\Models\Vehicule;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

class DriverVehicleOvernightController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('role:Admin|ofis|transport|Superadmin');
    }

    public function index(Request $request)
    {
        if (!Schema::hasTable('driver_vehicle_overnights')) {
            return view('driver_vehicle_overnights.index', [
                'missingTable' => true,
                'overnights' => collect(),
                'summaryByDriver' => collect(),
                'drivers' => collect(),
                'startDate' => now()->startOfMonth()->toDateString(),
                'endDate' => now()->endOfMonth()->toDateString(),
                'selectedDriverId' => null,
            ]);
        }

        $startDate = $request->input('start_date', now()->startOfMonth()->toDateString());
        $endDate = $request->input('end_date', now()->endOfMonth()->toDateString());
        $selectedDriverId = $request->input('driver_id');

        $baseQuery = DriverVehicleOvernight::with(['driver', 'vehicule', 'transfer', 'post.acente', 'creator'])
            ->whereNull('deleted_at')
            ->whereBetween('overnight_date', [$startDate, $endDate])
            ->when($selectedDriverId, fn ($q) => $q->where('driver_id', $selectedDriverId));

        $overnights = (clone $baseQuery)
            ->orderByDesc('overnight_date')
            ->orderByDesc('id')
            ->paginate(50)
            ->appends($request->query());

        $summaryByDriver = (clone $baseQuery)
            ->get()
            ->groupBy('driver_id')
            ->map(function ($rows) {
                $first = $rows->first();

                return [
                    'driver' => optional($first->driver)->name ?? 'Chauffeur non défini',
                    'count' => $rows->count(),
                    'amount' => $rows->sum(fn ($row) => (float) $row->amount),
                ];
            })
            ->sortBy('driver');

        $drivers = Acente::orderBy('name')->pluck('name', 'id');

        return view('driver_vehicle_overnights.index', compact(
            'overnights',
            'summaryByDriver',
            'drivers',
            'startDate',
            'endDate',
            'selectedDriverId'
        ) + ['missingTable' => false]);
    }

    public function create(Request $request)
    {
        $overnight = new DriverVehicleOvernight();
        $transfer = null;

        if ($request->filled('transfer_id')) {
            $transfer = Transfer::with(['driver', 'vehicule', 'post'])->find($request->input('transfer_id'));

            if ($transfer) {
                $start = Carbon::parse($transfer->start_date);
                $overnight->overnight_date = $start->copy()->subDay()->toDateString();
                $overnight->driver_id = $transfer->driver_id;
                $overnight->vehicule_id = $transfer->vehicule_id;
                $overnight->transfer_id = $transfer->id;
                $overnight->post_id = $transfer->post_id;
                $overnight->city = $this->guessCity($transfer->target);
                $overnight->address = $transfer->target;
                $overnight->google_address = optional($transfer->trajets()->orderByDesc('order')->first())->google_address ?: $transfer->target;
                $overnight->reason = 'decoucher';
            }
        }

        return view('driver_vehicle_overnights.create', $this->formData($overnight, $transfer));
    }

    public function store(Request $request)
    {
        $data = $this->validatedData($request);
        $additionalNights = $this->validatedAdditionalNights($request);
        $data['created_by'] = Auth::id();

        DriverVehicleOvernight::create($data);

        foreach ($additionalNights as $night) {
            DriverVehicleOvernight::create(array_merge($data, [
                'overnight_date' => $night['overnight_date'],
                'city' => $night['city'] ?? null,
                'address' => $night['address'] ?? null,
                'google_address' => $night['google_address'] ?? null,
                'notes' => $night['notes'] ?? null,
                'amount' => $night['amount'] ?? null,
            ]));
        }

        return redirect()->route('driver-vehicle-overnights.index')
            ->with('flash_message', (count($additionalNights) + 1).' nuit(s) de découcher enregistrée(s).');
    }

    public function edit(DriverVehicleOvernight $driverVehicleOvernight)
    {
        return view('driver_vehicle_overnights.edit', $this->formData($driverVehicleOvernight, $driverVehicleOvernight->transfer));
    }

    public function update(Request $request, DriverVehicleOvernight $driverVehicleOvernight)
    {
        $driverVehicleOvernight->update($this->validatedData($request));

        return redirect()->route('driver-vehicle-overnights.index')
            ->with('flash_message', 'Découcher mis à jour.');
    }

    public function destroy(DriverVehicleOvernight $driverVehicleOvernight)
    {
        $driverVehicleOvernight->delete();

        return redirect()->route('driver-vehicle-overnights.index')
            ->with('flash_message', 'Découcher supprimé.');
    }

    private function formData(DriverVehicleOvernight $overnight, ?Transfer $transfer = null): array
    {
        return [
            'overnight' => $overnight,
            'transfer' => $transfer,
            'drivers' => Acente::orderBy('name')->pluck('name', 'id'),
            'vehicules' => Vehicule::whereNull('sales')->orderBy('name')->pluck('name', 'id'),
            'reasons' => [
                'decoucher' => 'Découcher',
                'hotel' => 'Hôtel',
                'tour' => 'Circuit / tournée',
                'long_distance' => 'Longue distance',
                'maintenance' => 'Maintenance',
                'other' => 'Autre',
            ],
        ];
    }

    private function validatedData(Request $request): array
    {
        return $request->validate([
            'overnight_date' => 'required|date',
            'driver_id' => 'nullable|integer',
            'vehicule_id' => 'nullable|integer',
            'transfer_id' => 'nullable|integer',
            'post_id' => 'nullable|integer',
            'city' => 'nullable|string|max:120',
            'address' => 'nullable|string|max:255',
            'google_address' => 'nullable|string|max:255',
            'reason' => 'nullable|string|max:120',
            'notes' => 'nullable|string',
            'amount' => 'nullable|numeric|min:0',
        ]);
    }

    private function validatedAdditionalNights(Request $request): array
    {
        $validated = $request->validate([
            'nights' => ['nullable', 'array'],
            'nights.*.overnight_date' => ['nullable', 'date'],
            'nights.*.city' => ['nullable', 'string', 'max:120'],
            'nights.*.address' => ['nullable', 'string', 'max:255'],
            'nights.*.google_address' => ['nullable', 'string', 'max:255'],
            'nights.*.notes' => ['nullable', 'string'],
            'nights.*.amount' => ['nullable', 'numeric', 'min:0'],
        ]);

        return collect($validated['nights'] ?? [])
            ->filter(function (array $night) {
                return !empty($night['overnight_date'])
                    && (
                        !empty($night['city'])
                        || !empty($night['address'])
                        || !empty($night['google_address'])
                        || !empty($night['notes'])
                    );
            })
            ->values()
            ->all();
    }

    private function guessCity(?string $value): ?string
    {
        if (!$value) {
            return null;
        }

        $parts = array_values(array_filter(array_map('trim', explode(',', $value))));

        return $parts ? end($parts) : null;
    }
}
