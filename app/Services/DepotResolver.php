<?php

namespace App\Services;

use App\Models\Option;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DepotResolver
{
    public function activeDepots(): Collection
    {
        if (!Schema::hasTable('depots')) {
            return collect([$this->fallbackDepot()]);
        }

        $query = DB::table('depots')->where(function ($query) {
            $query->whereNull('active')->orWhere('active', 1);
        });

        if (Schema::hasColumn('depots', 'deleted_at')) {
            $query->whereNull('deleted_at');
        }

        $depots = $query
            ->orderByDesc('is_default')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return $depots->isNotEmpty() ? $depots : collect([$this->fallbackDepot()]);
    }

    public function defaultDepot(): object
    {
        if (!Schema::hasTable('depots')) {
            return $this->fallbackDepot();
        }

        $query = DB::table('depots')->where(function ($query) {
            $query->whereNull('active')->orWhere('active', 1);
        });

        if (Schema::hasColumn('depots', 'deleted_at')) {
            $query->whereNull('deleted_at');
        }

        $default = (clone $query)
            ->where('is_default', 1)
            ->orderBy('sort_order')
            ->first();

        return $default ?: ($this->activeDepots()->first() ?: $this->fallbackDepot());
    }

    public function find(?int $depotId): ?object
    {
        if (!$depotId || !Schema::hasTable('depots')) {
            return null;
        }

        $query = DB::table('depots')->where('id', $depotId);

        if (Schema::hasColumn('depots', 'deleted_at')) {
            $query->whereNull('deleted_at');
        }

        return $query->first();
    }

    public function depotForVehicle(?int $vehiculeId): ?object
    {
        if (!$vehiculeId || !Schema::hasTable('vehicules') || !Schema::hasColumn('vehicules', 'depot_id')) {
            return null;
        }

        $depotId = DB::table('vehicules')->where('id', $vehiculeId)->value('depot_id');

        return $this->find($depotId ? (int) $depotId : null);
    }

    public function resolve(?int $depotId = null, ?int $vehiculeId = null): object
    {
        return $this->find($depotId)
            ?: $this->depotForVehicle($vehiculeId)
            ?: $this->defaultDepot();
    }

    public function defaultAddress(): string
    {
        return $this->addressForDepot($this->defaultDepot());
    }

    public function addressForDepot(?object $depot): string
    {
        $address = trim((string) ($depot->address ?? ''));

        return $address !== '' ? $address : '3 Rue de la Butte, Drancy, France';
    }

    private function fallbackDepot(): object
    {
        $address = trim((string) Option::where('name', 'enroute_default_depot_address')->value('value'));

        if ($address === '' || stripos($address, 'ADRES DEPOT') !== false) {
            $address = '3 Rue de la Butte, Drancy, France';
        }

        return (object) [
            'id' => null,
            'name' => 'Dépôt Paris',
            'code' => 'PARIS',
            'address' => $address,
            'city' => 'Paris / Drancy',
            'is_default' => 1,
            'active' => 1,
        ];
    }
}
