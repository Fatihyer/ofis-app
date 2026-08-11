<?php

namespace App\Http\Controllers;

use App\Models\Depot;
use App\Models\Vehicule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class DepotController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('permission:rates.manage');
    }

    public function index()
    {
        $depots = Depot::orderByDesc('is_default')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $vehicules = Schema::hasColumn('vehicules', 'depot_id')
            ? Vehicule::query()
                ->where(function ($query) {
                    $query->whereNull('sales')->orWhere('sales', '!=', 1);
                })
                ->orderBy('name')
                ->get()
            : collect();

        return view('depots.index', compact('depots', 'vehicules'));
    }

    public function store(Request $request)
    {
        $data = $this->validatedData($request);
        $data['active'] = (int) ($data['active'] ?? 0);
        $data['is_default'] = (int) ($data['is_default'] ?? 0);

        DB::transaction(function () use ($data) {
            if (!empty($data['is_default'])) {
                Depot::query()->update(['is_default' => 0]);
            }

            Depot::create($data);
        });

        return redirect()
            ->route('depots.index')
            ->with('success', 'Le dépôt a été ajouté.');
    }

    public function update(Request $request, Depot $depot)
    {
        $data = $this->validatedData($request, $depot->id);
        $data['active'] = (int) ($data['active'] ?? 0);
        $data['is_default'] = (int) ($data['is_default'] ?? 0);

        DB::transaction(function () use ($depot, $data) {
            if (!empty($data['is_default'])) {
                Depot::where('id', '!=', $depot->id)->update(['is_default' => 0]);
            }

            $depot->update($data);

            if (!Depot::where('is_default', 1)->exists()) {
                Depot::where('active', 1)->orderBy('sort_order')->orderBy('id')->limit(1)->update(['is_default' => 1]);
            }
        });

        return redirect()
            ->route('depots.index')
            ->with('success', 'Le dépôt a été mis à jour.');
    }

    public function destroy(Depot $depot)
    {
        $vehicleCount = Schema::hasColumn('vehicules', 'depot_id')
            ? Vehicule::where('depot_id', $depot->id)->count()
            : 0;
        $transferCount = Schema::hasColumn('transfers', 'depot_id')
            ? DB::table('transfers')->where('depot_id', $depot->id)->count()
            : 0;

        if ($vehicleCount || $transferCount) {
            return redirect()
                ->route('depots.index')
                ->withErrors('Ce dépôt est utilisé par des véhicules ou des transferts. Désactivez-le au lieu de le supprimer.');
        }

        $depot->delete();

        return redirect()
            ->route('depots.index')
            ->with('success', 'Le dépôt a été supprimé.');
    }

    public function updateVehicle(Request $request)
    {
        $data = $request->validate([
            'vehicule_id' => 'required|integer|exists:vehicules,id',
            'depot_id' => 'nullable|integer|exists:depots,id',
        ]);

        abort_unless(Schema::hasColumn('vehicules', 'depot_id'), 404);

        Vehicule::where('id', $data['vehicule_id'])->update([
            'depot_id' => $data['depot_id'] ?: null,
        ]);

        return redirect()
            ->route('depots.index')
            ->with('success', 'Le dépôt du véhicule a été mis à jour.');
    }

    private function validatedData(Request $request, ?int $ignoreId = null): array
    {
        return $request->validate([
            'name' => 'required|string|max:120',
            'code' => [
                'nullable',
                'string',
                'max:30',
                Rule::unique('depots', 'code')->ignore($ignoreId),
            ],
            'address' => 'required|string|max:255',
            'city' => 'nullable|string|max:120',
            'postal_code' => 'nullable|string|max:20',
            'country' => 'nullable|string|max:80',
            'lat' => 'nullable|numeric',
            'lng' => 'nullable|numeric',
            'sort_order' => 'nullable|integer|min:0',
            'is_default' => 'nullable|boolean',
            'active' => 'nullable|boolean',
        ]);
    }
}
