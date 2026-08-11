<?php

namespace App\Services;

use App\Models\Option;
use App\Models\Servicetype;
use App\Models\Vehicule;
use App\Models\DriverVehicleOvernight;
use App\Services\Routing\GoogleRouteService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Throwable;

class TransferEnRouteCalculator
{
    private array $options = [];

    public function __construct(
        private GoogleRouteService $googleRouteService,
        private DepotResolver $depotResolver
    ) {
    }

    public function calculate(array $trajets, ?int $servicetypeId = null, ?int $vehiculeId = null, ?int $driverId = null, ?int $depotId = null): ?Carbon
    {
        $trajets = $this->sortTrajets($trajets);
        $firstDepot = $this->firstTrajetOfType($trajets, 'depot');

        if ($firstDepot && !empty($firstDepot['datetime'])) {
            return Carbon::parse($firstDepot['datetime']);
        }

        $firstReal = $this->firstRealTrajet($trajets);
        if (!$firstReal || empty($firstReal['datetime'])) {
            return null;
        }

        $pickupAt = Carbon::parse($firstReal['datetime']);
        $pickupAddress = $this->addressForTrajet($firstReal);
        $lastReal = $this->lastRealTrajet($trajets);
        $dropoffAddress = $lastReal ? $this->addressForTrajet($lastReal) : '';

        $originAddress = $this->originAddress($pickupAt, $driverId, $vehiculeId, $depotId);
        $googleMinutes = $this->googleTravelMinutes($originAddress, $pickupAddress);
        $travelMinutes = $googleMinutes ?: $this->optionInt('enroute_default_minutes', 60);

        $bufferMinutes =
            $this->zoneBufferMinutes($pickupAddress)
            + $this->vehicleBufferMinutes($vehiculeId, $servicetypeId)
            + $this->airportBufferMinutes($pickupAddress, $dropoffAddress, $servicetypeId);

        $leadMinutes = max(
            $this->optionInt('enroute_minimum_minutes', 120),
            $travelMinutes + $bufferMinutes
        );

        $planned = $pickupAt->copy()->subMinutes($leadMinutes);

        return $this->roundDown($planned, $this->optionInt('enroute_round_minutes', 5));
    }

    public function hasDepot(array $trajets): bool
    {
        return (bool) $this->firstTrajetOfType($trajets, 'depot');
    }

    public function defaultDepotAddress(): string
    {
        return $this->depotResolver->defaultAddress();
    }

    private function googleTravelMinutes(string $originAddress, string $pickupAddress): ?int
    {
        $depotAddress = trim($originAddress) ?: $this->defaultDepotAddress();
        $pickupAddress = trim($pickupAddress);

        if ($depotAddress === '' || $pickupAddress === '') {
            return null;
        }

        try {
            $route = $this->googleRouteService->compute($depotAddress, $pickupAddress);
            $seconds = (int) ($route['duration_seconds'] ?? 0);

            return $seconds > 0 ? (int) ceil($seconds / 60) : null;
        } catch (Throwable $e) {
            Log::warning('En route Google calculation failed', [
                'depot' => $depotAddress,
                'pickup' => $pickupAddress,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    private function originAddress(Carbon $pickupAt, ?int $driverId, ?int $vehiculeId, ?int $depotId = null): string
    {
        $overnight = $this->overnightFor($pickupAt, $driverId, $vehiculeId);

        if ($overnight) {
            return trim((string) ($overnight->google_address ?: $overnight->address));
        }

        return $this->depotResolver->addressForDepot($this->depotResolver->resolve($depotId, $vehiculeId));
    }

    private function overnightFor(Carbon $pickupAt, ?int $driverId, ?int $vehiculeId): ?DriverVehicleOvernight
    {
        if (!$driverId || !Schema::hasTable('driver_vehicle_overnights')) {
            return null;
        }

        $overnightDates = [
            $pickupAt->copy()->subDay()->toDateString(),
            $pickupAt->copy()->toDateString(),
        ];

        $base = DriverVehicleOvernight::whereNull('deleted_at')
            ->whereIn('overnight_date', $overnightDates)
            ->where('driver_id', $driverId)
            ->where(function ($query) {
                $query->whereNotNull('google_address')
                    ->whereRaw("TRIM(google_address) != ''")
                    ->orWhere(function ($subQuery) {
                        $subQuery->whereNotNull('address')
                            ->whereRaw("TRIM(address) != ''");
                    });
            })
            ->orderByRaw("FIELD(overnight_date, ?, ?)", $overnightDates)
            ->orderByDesc('id');

        if ($vehiculeId) {
            $exact = (clone $base)->where('vehicule_id', $vehiculeId)->first();
            if ($exact) {
                return $exact;
            }
        }

        return $base->first();
    }

    private function zoneBufferMinutes(string $address): int
    {
        $address = $this->normalize($address);

        if (preg_match('/\b75\d{3}\b/', $address) || str_contains($address, 'paris')) {
            return $this->optionInt('enroute_buffer_paris_intramuros', 20);
        }

        if (preg_match('/\b(92|93|94)\d{3}\b/', $address)) {
            return $this->optionInt('enroute_buffer_petite_couronne', 25);
        }

        if (preg_match('/\b(77|78|91|95)\d{3}\b/', $address)) {
            return $this->optionInt('enroute_buffer_grande_couronne', 30);
        }

        return $this->optionInt('enroute_buffer_long_distance', 45);
    }

    private function vehicleBufferMinutes(?int $vehiculeId, ?int $servicetypeId): int
    {
        $label = '';

        if ($vehiculeId) {
            $vehicule = Vehicule::find($vehiculeId);
            $label .= ' ' . ($vehicule->name ?? '');
        }

        if ($servicetypeId) {
            $service = Servicetype::find($servicetypeId);
            $label .= ' ' . ($service->name ?? '');
        }

        $label = $this->normalize($label);

        if (preg_match('/coach|autocar|bus|tourismo|otokar|setra|temsa|bova|iveco|55 places|55p/', $label)) {
            return $this->optionInt('enroute_vehicle_buffer_coach', 20);
        }

        if (str_contains($label, 'sprinter')) {
            return $this->optionInt('enroute_vehicle_buffer_sprinter', 10);
        }

        if (preg_match('/van|classe v|vito|viano|minivan/', $label)) {
            return $this->optionInt('enroute_vehicle_buffer_van', 5);
        }

        return 0;
    }

    private function airportBufferMinutes(string $pickupAddress, string $dropoffAddress, ?int $servicetypeId): int
    {
        $serviceName = '';
        if ($servicetypeId) {
            $service = Servicetype::find($servicetypeId);
            $serviceName = $this->normalize((string) ($service->name ?? ''));
        }

        $pickupAirport = $this->airportCode($pickupAddress);
        if ($pickupAirport || str_contains($serviceName, 'arrival') || str_contains($serviceName, 'arrivee')) {
            return $this->airportOption('arrival', $pickupAirport ?: $this->airportCode($dropoffAddress));
        }

        $dropoffAirport = $this->airportCode($dropoffAddress);
        if ($dropoffAirport || str_contains($serviceName, 'depart')) {
            return $this->airportOption('depart', $dropoffAirport ?: $pickupAirport);
        }

        return 0;
    }

    private function airportCode(string $address): ?string
    {
        $address = $this->normalize($address);

        if (str_contains($address, 'cdg') || str_contains($address, 'charles de gaulle') || str_contains($address, 'roissy')) {
            return 'cdg';
        }

        if (str_contains($address, 'ory') || str_contains($address, 'orly')) {
            return 'ory';
        }

        if (str_contains($address, 'bva') || str_contains($address, 'beauvais')) {
            return 'bva';
        }

        return null;
    }

    private function airportOption(string $direction, ?string $airport): int
    {
        if (!$airport) {
            return 0;
        }

        return $this->optionInt("enroute_airport_{$direction}_{$airport}", 0);
    }

    private function roundDown(Carbon $date, int $minutes): Carbon
    {
        if ($minutes <= 1) {
            return $date->second(0);
        }

        $minute = (int) floor($date->minute / $minutes) * $minutes;

        return $date->minute($minute)->second(0);
    }

    private function firstRealTrajet(array $trajets): ?array
    {
        foreach ($trajets as $trajet) {
            if (($trajet['type'] ?? null) !== 'depot') {
                return $trajet;
            }
        }

        return null;
    }

    private function lastRealTrajet(array $trajets): ?array
    {
        foreach (array_reverse($trajets) as $trajet) {
            if (($trajet['type'] ?? null) !== 'depot') {
                return $trajet;
            }
        }

        return null;
    }

    private function firstTrajetOfType(array $trajets, string $type): ?array
    {
        foreach ($trajets as $trajet) {
            if (($trajet['type'] ?? null) === $type) {
                return $trajet;
            }
        }

        return null;
    }

    private function addressForTrajet(array $trajet): string
    {
        return trim((string) ($trajet['google_address'] ?? '')) ?: trim((string) ($trajet['from'] ?? ''));
    }

    private function sortTrajets(array $trajets): array
    {
        usort($trajets, fn ($a, $b) => strtotime($a['datetime'] ?? '') <=> strtotime($b['datetime'] ?? ''));

        return $trajets;
    }

    private function optionInt(string $name, int $default): int
    {
        return (int) $this->option($name, $default);
    }

    private function option(string $name, $default)
    {
        if (!array_key_exists($name, $this->options)) {
            $this->options[$name] = Option::where('name', $name)->value('value');
        }

        return $this->options[$name] !== null && $this->options[$name] !== '' ? $this->options[$name] : $default;
    }

    private function normalize(string $value): string
    {
        $value = mb_strtolower($value, 'UTF-8');
        $value = strtr($value, [
            'à' => 'a',
            'â' => 'a',
            'ä' => 'a',
            'ç' => 'c',
            'é' => 'e',
            'è' => 'e',
            'ê' => 'e',
            'ë' => 'e',
            'î' => 'i',
            'ï' => 'i',
            'ô' => 'o',
            'ö' => 'o',
            'ù' => 'u',
            'û' => 'u',
            'ü' => 'u',
        ]);

        return $value;
    }
}
