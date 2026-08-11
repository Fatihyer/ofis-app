<?php

namespace App\Services\Pricing;

use App\Models\Talep;
use App\Models\TalepDay;
use App\Models\TalepQuote;
use App\Models\Option;
use App\Models\VehiclePriceRule;
use App\Services\Routing\GoogleRouteService;
use App\Services\DepotResolver;
use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class TalepPricingService
{
    public function __construct(
        private GoogleRouteService $googleRouteService,
        private DepotResolver $depotResolver
    ) {
    }

    public function calculate(Talep $talep, bool $refreshRoutes = true, bool $syncProposedPrice = false): TalepQuote
    {
        $talep->loadMissing(['days.route', 'quote']);

        $lines = [];
        $subtotal = 0.0;
        $extrasTotal = 0.0;
        $marginPercent = null;
        $vatRate = null;

        foreach ($talep->days as $day) {
            $line = $this->calculateDay($day, $talep, $refreshRoutes);
            $lines[] = $line;

            if ($line['matched']) {
                $subtotal += $line['base_total'];
                $extrasTotal += $line['extras_total'] + $line['date_adjustment_total'];
                $marginPercent ??= $line['margin_percent'];
                $vatRate ??= $line['vat_rate'];
                $day->forceFill([
                    'system_price' => $line['total_ht'],
                    'distance_meters' => $line['distance_meters'] ?: $day->distance_meters,
                    'duration_seconds' => $line['duration_seconds'] ?: $day->duration_seconds,
                    'traffic_duration_seconds' => $line['traffic_duration_seconds'] ?: $day->traffic_duration_seconds,
                    'toll_amount' => $line['toll_amount'] ?: $day->toll_amount,
                    'toll_currency' => $line['toll_currency'] ?: $day->toll_currency,
                    'fuel_amount' => $line['fuel_amount'] ?: $day->fuel_amount,
                    'fuel_liters' => $line['fuel_liters'] ?: $day->fuel_liters,
                ] + (Schema::hasColumn('talep_days', 'depot_id') ? [
                    'depot_id' => data_get($line, 'parts.depot_id') ?: $day->depot_id,
                ] : []))->save();
            }
        }

        $marginPercent ??= 0.0;
        $vatRate ??= 10.0;

        $marginAmount = round(($subtotal + $extrasTotal) * ($marginPercent / 100), 2);
        $totalHt = round($subtotal + $extrasTotal + $marginAmount, 2);
        $vatAmount = round($totalHt * ($vatRate / 100), 2);
        $systemTotal = round($totalHt + $vatAmount, 2);

        $quote = $talep->quote ?: new TalepQuote(['talep_id' => $talep->id]);
        $quote->fill([
            'subtotal' => round($subtotal, 2),
            'extras_total' => round($extrasTotal, 2),
            'margin_percent' => $marginPercent,
            'margin_amount' => $marginAmount,
            'vat_rate' => $vatRate,
            'vat_amount' => $vatAmount,
            'system_total' => $systemTotal,
            'final_total' => $quote->is_manual_override ? $quote->final_total : $systemTotal,
            'currency' => $talep->currency ?: 'EUR',
            'calculation_json' => [
                'lines' => $lines,
                'refresh_routes' => $refreshRoutes,
            ],
            'calculated_at' => now(),
        ])->save();

        if ($syncProposedPrice) {
            $talep->forceFill([
                'system_total' => $systemTotal,
                'currency' => $talep->currency ?: 'EUR',
            ])->save();
        }

        return $quote;
    }

    private function calculateDay(TalepDay $day, Talep $talep, bool $refreshRoutes): array
    {
        $route = $this->routeData($day, $refreshRoutes);
        $route = $this->withDepotPricingRoute($day, $talep, $route, $refreshRoutes);
        $pricingDistanceMeters = $route['pricing_distance_meters'] ?: $route['distance_meters'] ?: $day->distance_meters ?: 0;
        $pricingDurationSeconds = $route['pricing_traffic_duration_seconds'] ?: $route['pricing_duration_seconds'] ?: $route['traffic_duration_seconds'] ?: $route['duration_seconds'] ?: $day->traffic_duration_seconds ?: $day->duration_seconds ?: 0;
        $distanceKm = round($pricingDistanceMeters / 1000, 2);
        $durationHours = round($pricingDurationSeconds / 3600, 2);

        $vehicleCode = $this->normalizeVehicleType($day->vehicle_type ?: $talep->vehicle_type, $day->pax ?: $talep->total_pax);
        $serviceCode = $this->normalizeServiceType($day->service_type ?: $talep->service_type);
        $rule = $this->findRule($vehicleCode, $serviceCode);

        if (!$rule) {
            return $this->line($day, null, $vehicleCode, $serviceCode, $distanceKm, $durationHours, $route, false, [
                'Aucune règle tarifaire active ne correspond au véhicule/service.',
            ]);
        }

        $extraKm = max(0, $distanceKm - (float) $rule->included_km);
        $extraHours = max(0, $durationHours - (float) $rule->included_hours);
        $nightHours = $this->nightHours($day);

        $base = max((float) $rule->base_rate, (float) $rule->minimum_charge);
        $extraKmAmount = round($extraKm * (float) $rule->extra_km_rate, 2);
        $extraHourAmount = round($extraHours * (float) $rule->extra_hour_rate, 2);
        $nightAmount = round($nightHours * (float) $rule->night_extra_hour_rate, 2);
        $tollAmount = (float) ($route['toll_amount'] ?: $day->toll_amount ?: 0);
        $fuelEstimate = $this->fuelEstimate($talep, $vehicleCode, $distanceKm);
        $fuelAmount = (float) ($fuelEstimate['amount'] ?: $day->fuel_amount ?: 0);
        $driverFees = $this->driverFees($talep, $day, $rule, $durationHours, $serviceCode);
        $dateAdjustments = $this->dateAdjustments($day, $vehicleCode, $serviceCode);

        return $this->line($day, $rule, $vehicleCode, $serviceCode, $distanceKm, $durationHours, $route, true, [
            'base' => $base,
            'extra_km_amount' => $extraKmAmount,
            'extra_hour_amount' => $extraHourAmount,
            'night_amount' => $nightAmount,
            'toll_amount' => $tollAmount,
            'fuel_amount' => $fuelAmount,
            'fuel_liters' => $fuelEstimate['liters'],
            'fuel_consumption_l_100km' => $fuelEstimate['consumption_l_100km'],
            'fuel_price_per_liter' => $fuelEstimate['price_per_liter'],
            'fuel_source' => $fuelEstimate['source'],
            'driver_meal_count' => $driverFees['meal_count'],
            'driver_meal_amount' => $driverFees['meal_amount'],
            'driver_hotel_count' => $driverFees['hotel_count'],
            'driver_hotel_amount' => $driverFees['hotel_amount'],
            'driver_fees_total' => $driverFees['total'],
            'date_adjustments' => $dateAdjustments,
            'depot_id' => $route['depot_id'] ?? null,
            'depot_name' => $route['depot_name'] ?? null,
            'depot_address' => $route['depot_address'] ?? null,
            'depot_source' => $route['depot_source'] ?? null,
            'commercial_distance_km' => round(($route['distance_meters'] ?: 0) / 1000, 2),
            'approach_distance_km' => round(($route['approach_distance_meters'] ?? 0) / 1000, 2),
            'return_depot_distance_km' => round(($route['return_depot_distance_meters'] ?? 0) / 1000, 2),
            'pricing_distance_km' => $distanceKm,
            'extra_km' => $extraKm,
            'extra_hours' => $extraHours,
            'night_hours' => $nightHours,
        ]);
    }

    private function line(TalepDay $day, ?VehiclePriceRule $rule, string $vehicleCode, string $serviceCode, float $distanceKm, float $durationHours, array $route, bool $matched, array $parts): array
    {
        $baseTotal = $matched ? round((float) $parts['base'] + (float) $parts['extra_km_amount'] + (float) $parts['extra_hour_amount'] + (float) $parts['night_amount'], 2) : 0.0;
        $extrasTotal = $matched ? round((float) $parts['toll_amount'] + (float) $parts['fuel_amount'] + (float) ($parts['driver_fees_total'] ?? 0), 2) : 0.0;
        $adjustmentTotal = $matched ? $this->dateAdjustmentAmount($baseTotal + $extrasTotal, $parts['date_adjustments'] ?? []) : 0.0;
        $totalHt = round($baseTotal + $extrasTotal + $adjustmentTotal, 2);

        return [
            'talep_day_id' => $day->id,
            'day_number' => $day->day_number,
            'matched' => $matched,
            'rule_id' => $rule?->id,
            'vehicle_code' => $vehicleCode,
            'service_code' => $serviceCode,
            'distance_km' => $distanceKm,
            'duration_hours' => $durationHours,
            'distance_meters' => $route['distance_meters'],
            'duration_seconds' => $route['duration_seconds'],
            'traffic_duration_seconds' => $route['traffic_duration_seconds'],
            'toll_amount' => $route['toll_amount'],
            'toll_currency' => $route['toll_currency'],
            'pricing_distance_km' => (float) ($parts['pricing_distance_km'] ?? $distanceKm),
            'commercial_distance_km' => (float) ($parts['commercial_distance_km'] ?? round(($route['distance_meters'] ?: 0) / 1000, 2)),
            'approach_distance_km' => (float) ($parts['approach_distance_km'] ?? 0),
            'return_depot_distance_km' => (float) ($parts['return_depot_distance_km'] ?? 0),
            'fuel_amount' => (float) ($parts['fuel_amount'] ?? 0),
            'fuel_liters' => (float) ($parts['fuel_liters'] ?? 0),
            'driver_fees_total' => (float) ($parts['driver_fees_total'] ?? 0),
            'base_total' => $baseTotal,
            'extras_total' => $extrasTotal,
            'date_adjustment_total' => $adjustmentTotal,
            'total_ht' => $totalHt,
            'margin_percent' => $rule ? (float) $rule->default_margin_percent : 0.0,
            'vat_rate' => $rule ? (float) $rule->vat_rate : 10.0,
            'parts' => $parts,
        ];
    }

    private function dateAdjustments(TalepDay $day, string $vehicleCode, string $serviceCode): array
    {
        if (!$day->service_date || !Schema::hasTable('vehicle_price_date_adjustments')) {
            return [];
        }

        $date = $day->service_date instanceof Carbon
            ? $day->service_date->toDateString()
            : Carbon::parse($day->service_date)->toDateString();

        return DB::table('vehicle_price_date_adjustments')
            ->where('active', 1)
            ->whereDate('start_date', '<=', $date)
            ->whereDate('end_date', '>=', $date)
            ->where(function ($query) use ($vehicleCode) {
                $query->whereNull('vehicle_type')->orWhere('vehicle_type', $vehicleCode);
            })
            ->where(function ($query) use ($serviceCode) {
                $query->whereNull('service_type')->orWhere('service_type', $serviceCode);
            })
            ->orderBy('start_date')
            ->get()
            ->map(fn ($row) => [
                'id' => $row->id,
                'label' => $row->label,
                'vehicle_type' => $row->vehicle_type,
                'service_type' => $row->service_type,
                'start_date' => $row->start_date,
                'end_date' => $row->end_date,
                'adjustment_type' => $row->adjustment_type,
                'direction' => $row->direction ?? 'increase',
                'adjustment_value' => (float) $row->adjustment_value,
            ])
            ->all();
    }

    private function fuelEstimate(Talep $talep, string $vehicleCode, float $distanceKm): array
    {
        $stats = $this->fuelStatsForVehicle($talep->vehicule_id);

        if (!$stats) {
            $stats = $this->fuelStatsForVehicleCode($vehicleCode);
        }

        $configuredConsumption = $this->configuredFuelConsumption($vehicleCode);
        $configuredPricePerLiter = $this->configuredFuelPricePerLiter();

        $consumption = $configuredConsumption ?? ($stats['consumption_l_100km'] ?? $this->defaultConsumption($vehicleCode));
        $pricePerLiter = $configuredPricePerLiter ?? ($stats['price_per_liter'] ?? $this->averageFuelPrice() ?? 2.05);
        $liters = round(($distanceKm * $consumption) / 100, 2);
        $amount = round($liters * $pricePerLiter, 2);

        return [
            'liters' => $liters,
            'amount' => $amount,
            'consumption_l_100km' => round($consumption, 2),
            'price_per_liter' => round($pricePerLiter, 3),
            'source' => $configuredConsumption ? 'option:' . $vehicleCode : ($stats['source'] ?? 'default'),
        ];
    }

    private function configuredFuelConsumption(string $vehicleCode): ?float
    {
        $value = Option::where('name', 'talepFuelConsumptions')->value('value');
        $decoded = json_decode((string) $value, true);

        if (!is_array($decoded)) {
            return null;
        }

        $candidate = $decoded[$vehicleCode] ?? $decoded['DEFAULT'] ?? null;

        if ($candidate === null || $candidate === '') {
            return null;
        }

        $consumption = (float) $candidate;

        return $consumption > 0 ? $consumption : null;
    }

    private function configuredFuelPricePerLiter(): ?float
    {
        $value = Option::where('name', 'talepFuelPricePerLiter')->value('value');

        if ($value === null || $value === '') {
            return null;
        }

        $price = (float) $value;

        return $price > 0 ? $price : null;
    }

    private function fuelStatsForVehicle(?int $vehicleId): ?array
    {
        if (!$vehicleId || !Schema::hasTable('fuel_purchases') || !Schema::hasTable('hermes_daily_stats')) {
            return null;
        }

        $from = now()->subMonths(6)->toDateString();
        $fuel = DB::table('fuel_purchases')
            ->where('vehicule_id', $vehicleId)
            ->where('purchase_date', '>=', $from)
            ->where('volume', '>', 0)
            ->where('amount', '>', 0)
            ->where(function ($query) {
                $query->whereNull('produit')->orWhere('produit', 'not like', '%AdBlue%');
            })
            ->selectRaw('SUM(volume) as liters, SUM(amount) as amount')
            ->first();

        $distanceKm = (float) DB::table('hermes_daily_stats')
            ->where('vehicule_id', $vehicleId)
            ->where('day', '>=', $from)
            ->sum('distance_km');

        if (!$fuel || (float) $fuel->liters <= 0 || $distanceKm < 100) {
            return null;
        }

        $consumption = ((float) $fuel->liters / $distanceKm) * 100;
        if ($consumption < 3 || $consumption > 80) {
            return null;
        }

        return [
            'consumption_l_100km' => $consumption,
            'price_per_liter' => (float) $fuel->amount / (float) $fuel->liters,
            'source' => 'vehicle:' . $vehicleId,
        ];
    }

    private function fuelStatsForVehicleCode(string $vehicleCode): ?array
    {
        if (!Schema::hasTable('fuel_purchases') || !Schema::hasTable('hermes_daily_stats') || !Schema::hasTable('vehicules')) {
            return null;
        }

        $from = now()->subMonths(6)->toDateString();
        $vehicleIds = DB::table('vehicules')
            ->where(function ($query) {
                $query->whereNull('sales')->orWhere('sales', '!=', 1);
            })
            ->get(['id', 'name', 'capacity', 'yil'])
            ->filter(fn ($vehicle) => $this->vehicleCodeFromVehicleRow($vehicle) === $vehicleCode)
            ->pluck('id')
            ->values()
            ->all();

        if (!$vehicleIds) {
            return null;
        }

        $distances = DB::table('hermes_daily_stats')
            ->whereIn('vehicule_id', $vehicleIds)
            ->where('day', '>=', $from)
            ->selectRaw('vehicule_id, SUM(distance_km) as distance_km')
            ->groupBy('vehicule_id')
            ->pluck('distance_km', 'vehicule_id');

        $eligibleVehicleIds = $distances
            ->filter(fn ($distanceKm) => (float) $distanceKm >= 100)
            ->keys()
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();

        if (!$eligibleVehicleIds) {
            return null;
        }

        $fuel = DB::table('fuel_purchases')
            ->whereIn('vehicule_id', $eligibleVehicleIds)
            ->where('purchase_date', '>=', $from)
            ->where('volume', '>', 0)
            ->where('amount', '>', 0)
            ->where(function ($query) {
                $query->whereNull('produit')->orWhere('produit', 'not like', '%AdBlue%');
            })
            ->selectRaw('SUM(volume) as liters, SUM(amount) as amount')
            ->first();

        $distanceKm = (float) $distances
            ->only($eligibleVehicleIds)
            ->sum();

        if (!$fuel || (float) $fuel->liters <= 0 || $distanceKm < 100) {
            return null;
        }

        $consumption = ((float) $fuel->liters / $distanceKm) * 100;
        if ($consumption < 3 || $consumption > 80) {
            return null;
        }

        return [
            'consumption_l_100km' => $consumption,
            'price_per_liter' => (float) $fuel->amount / (float) $fuel->liters,
            'source' => 'vehicle_code:' . $vehicleCode,
        ];
    }

    private function averageFuelPrice(): ?float
    {
        if (!Schema::hasTable('fuel_purchases')) {
            return null;
        }

        $fuel = DB::table('fuel_purchases')
            ->where('purchase_date', '>=', now()->subMonths(6)->toDateString())
            ->where('volume', '>', 0)
            ->where('amount', '>', 0)
            ->where(function ($query) {
                $query->whereNull('produit')->orWhere('produit', 'not like', '%AdBlue%');
            })
            ->selectRaw('SUM(volume) as liters, SUM(amount) as amount')
            ->first();

        if (!$fuel || (float) $fuel->liters <= 0) {
            return null;
        }

        return (float) $fuel->amount / (float) $fuel->liters;
    }

    private function vehicleCodeFromVehicleRow(object $vehicle): string
    {
        $name = trim(($vehicle->name ?? '') . ' ' . ($vehicle->yil ?? '') . ' ' . ($vehicle->capacity ?? ''));
        return $this->normalizeVehicleType($name, (int) ($vehicle->capacity ?? 0));
    }

    private function defaultConsumption(string $vehicleCode): float
    {
        return match ($vehicleCode) {
            'SEDAN_4' => 7.5,
            'CLASS_V' => 9.5,
            'VAN_8' => 10.5,
            'SPRINTER_19' => 14.5,
            'MINIBUS_30' => 18.0,
            'COACH_45' => 26.0,
            'COACH_50' => 30.0,
            'COACH_55' => 32.0,
            'COACH_60' => 34.0,
            default => 15.0,
        };
    }

    private function driverFees(Talep $talep, TalepDay $day, VehiclePriceRule $rule, float $durationHours, string $serviceCode): array
    {
        $mealCount = $this->driverMealCount($day, $durationHours, $serviceCode);
        $hotelCount = $this->driverHotelCount($talep, $day);
        $mealAmount = round($mealCount * (float) $rule->driver_meal_cost, 2);
        $hotelAmount = round($hotelCount * (float) $rule->driver_hotel_cost, 2);

        return [
            'meal_count' => $mealCount,
            'meal_amount' => $mealAmount,
            'hotel_count' => $hotelCount,
            'hotel_amount' => $hotelAmount,
            'total' => round($mealAmount + $hotelAmount, 2),
        ];
    }

    private function driverMealCount(TalepDay $day, float $durationHours, string $serviceCode): int
    {
        if ($durationHours >= 10) {
            return 2;
        }

        if ($durationHours >= 6 || $serviceCode === 'dispo') {
            return 1;
        }

        if (!$day->start_time || !$day->end_time) {
            return 0;
        }

        try {
            $start = Carbon::parse($day->start_time);
            $end = Carbon::parse($day->end_time);

            if ($end->lessThanOrEqualTo($start)) {
                $end->addDay();
            }

            $mealWindows = [
                ['11:30', '14:30'],
                ['19:00', '22:00'],
            ];

            $count = 0;
            foreach ($mealWindows as [$windowStart, $windowEnd]) {
                $from = Carbon::parse($start->toDateString() . ' ' . $windowStart);
                $to = Carbon::parse($start->toDateString() . ' ' . $windowEnd);

                if ($start->lessThanOrEqualTo($to) && $end->greaterThanOrEqualTo($from)) {
                    $count++;
                }
            }

            return min($count, 2);
        } catch (\Throwable $e) {
            return 0;
        }
    }

    private function driverHotelCount(Talep $talep, TalepDay $day): int
    {
        $days = $talep->days
            ->filter(fn (TalepDay $item) => !empty($item->service_date))
            ->sortBy(fn (TalepDay $item) => $item->service_date->format('Y-m-d') . '-' . str_pad((string) $item->day_number, 3, '0', STR_PAD_LEFT))
            ->values();

        if ($days->count() <= 1 || !$day->service_date) {
            return 0;
        }

        $lastDay = $days->last();

        return $lastDay && (int) $lastDay->id !== (int) $day->id ? 1 : 0;
    }

    private function dateAdjustmentAmount(float $lineBase, array $adjustments): float
    {
        $total = 0.0;

        foreach ($adjustments as $adjustment) {
            $sign = ($adjustment['direction'] ?? 'increase') === 'discount' ? -1 : 1;

            if (($adjustment['adjustment_type'] ?? 'percent') === 'fixed') {
                $total += $sign * (float) $adjustment['adjustment_value'];
                continue;
            }

            $total += $sign * $lineBase * ((float) $adjustment['adjustment_value'] / 100);
        }

        return round($total, 2);
    }

    private function withDepotPricingRoute(TalepDay $day, Talep $talep, array $route, bool $refreshRoutes): array
    {
        $commercialDistance = (int) ($route['distance_meters'] ?: $day->distance_meters ?: 0);
        $commercialDuration = (int) ($route['duration_seconds'] ?: $day->duration_seconds ?: 0);
        $commercialTrafficDuration = (int) ($route['traffic_duration_seconds'] ?: $day->traffic_duration_seconds ?: $commercialDuration);
        $pickup = trim((string) $day->pickup_location);
        $dropoff = trim((string) $day->dropoff_location);
        $candidates = $this->candidateDepots($day, $talep);

        $best = null;

        foreach ($candidates as $candidate) {
            $depotAddress = $this->depotResolver->addressForDepot($candidate['depot']);
            $approach = $this->emptyLegRoute($depotAddress, $pickup, $refreshRoutes);
            $returnDepot = $this->emptyLegRoute($dropoff, $depotAddress, $refreshRoutes);

            $pricingDistance = $commercialDistance + (int) $approach['distance_meters'] + (int) $returnDepot['distance_meters'];
            $pricingDuration = $commercialDuration + (int) $approach['duration_seconds'] + (int) $returnDepot['duration_seconds'];
            $pricingTrafficDuration = $commercialTrafficDuration + (int) ($approach['traffic_duration_seconds'] ?: $approach['duration_seconds']) + (int) ($returnDepot['traffic_duration_seconds'] ?: $returnDepot['duration_seconds']);

            $choice = [
                'depot' => $candidate['depot'],
                'source' => $candidate['source'],
                'approach' => $approach,
                'return_depot' => $returnDepot,
                'pricing_distance_meters' => $pricingDistance,
                'pricing_duration_seconds' => $pricingDuration,
                'pricing_traffic_duration_seconds' => $pricingTrafficDuration,
            ];

            if (!$best || $pricingDistance < $best['pricing_distance_meters']) {
                $best = $choice;
            }
        }

        if (!$best) {
            $depot = $this->depotResolver->defaultDepot();
            $best = [
                'depot' => $depot,
                'source' => 'default',
                'approach' => $this->emptyLegFallback(),
                'return_depot' => $this->emptyLegFallback(),
                'pricing_distance_meters' => $commercialDistance,
                'pricing_duration_seconds' => $commercialDuration,
                'pricing_traffic_duration_seconds' => $commercialTrafficDuration,
            ];
        }

        $depot = $best['depot'];

        return $route + [
            'depot_id' => $depot->id ?? null,
            'depot_name' => $depot->name ?? 'Dépôt',
            'depot_address' => $this->depotResolver->addressForDepot($depot),
            'depot_source' => $best['source'],
            'approach_distance_meters' => (int) $best['approach']['distance_meters'],
            'approach_duration_seconds' => (int) $best['approach']['duration_seconds'],
            'return_depot_distance_meters' => (int) $best['return_depot']['distance_meters'],
            'return_depot_duration_seconds' => (int) $best['return_depot']['duration_seconds'],
            'pricing_distance_meters' => (int) $best['pricing_distance_meters'],
            'pricing_duration_seconds' => (int) $best['pricing_duration_seconds'],
            'pricing_traffic_duration_seconds' => (int) $best['pricing_traffic_duration_seconds'],
        ];
    }

    private function candidateDepots(TalepDay $day, Talep $talep): array
    {
        $manualDepotId = null;
        if (Schema::hasColumn('talep_days', 'depot_id')) {
            $manualDepotId = $day->depot_id ? (int) $day->depot_id : null;
        }
        if (!$manualDepotId && Schema::hasColumn('talepler', 'depot_id')) {
            $manualDepotId = $talep->depot_id ? (int) $talep->depot_id : null;
        }

        if ($manualDepotId && ($depot = $this->depotResolver->find($manualDepotId))) {
            return [['depot' => $depot, 'source' => 'manual']];
        }

        if ($talep->vehicule_id && ($depot = $this->depotResolver->depotForVehicle((int) $talep->vehicule_id))) {
            return [['depot' => $depot, 'source' => 'vehicule']];
        }

        return $this->depotResolver->activeDepots()
            ->map(fn ($depot) => ['depot' => $depot, 'source' => 'auto'])
            ->values()
            ->all();
    }

    private function emptyLegRoute(string $origin, string $destination, bool $refreshRoutes): array
    {
        $origin = trim($origin);
        $destination = trim($destination);

        if (!$refreshRoutes || $origin === '' || $destination === '') {
            return $this->emptyLegFallback();
        }

        try {
            $route = $this->googleRouteService->compute($origin, $destination);

            return [
                'distance_meters' => (int) Arr::get($route, 'distance_meters', 0),
                'duration_seconds' => (int) Arr::get($route, 'duration_seconds', 0),
                'traffic_duration_seconds' => (int) Arr::get($route, 'traffic_duration_seconds', 0),
            ];
        } catch (\Throwable $e) {
            \Log::warning('Talep pricing depot leg calculation failed', [
                'origin' => $origin,
                'destination' => $destination,
                'error' => $e->getMessage(),
            ]);

            return $this->emptyLegFallback();
        }
    }

    private function emptyLegFallback(): array
    {
        return [
            'distance_meters' => 0,
            'duration_seconds' => 0,
            'traffic_duration_seconds' => 0,
        ];
    }

    private function routeData(TalepDay $day, bool $refreshRoutes): array
    {
        $existing = [
            'distance_meters' => $day->distance_meters,
            'duration_seconds' => $day->duration_seconds,
            'traffic_duration_seconds' => $day->traffic_duration_seconds,
            'toll_amount' => $day->toll_amount,
            'toll_currency' => $day->toll_currency ?: 'EUR',
        ];

        if (!$refreshRoutes || !$day->pickup_location || !$day->dropoff_location) {
            return $existing;
        }

        try {
            $computed = $this->googleRouteService->compute(
                $day->pickup_location,
                $day->dropoff_location,
                $this->usableRouteStops((array) $day->via_points_json)
            );

            return [
                'distance_meters' => Arr::get($computed, 'distance_meters'),
                'duration_seconds' => Arr::get($computed, 'duration_seconds'),
                'traffic_duration_seconds' => Arr::get($computed, 'traffic_duration_seconds'),
                'toll_amount' => Arr::get($computed, 'toll_amount'),
                'toll_currency' => Arr::get($computed, 'toll_currency', 'EUR'),
            ];
        } catch (\Throwable $e) {
            \Log::warning('Talep pricing route calculation failed', [
                'talep_day_id' => $day->id,
                'error' => $e->getMessage(),
            ]);

            return $existing;
        }
    }

    private function usableRouteStops(array $stops): array
    {
        return collect($stops)
            ->map(fn ($stop) => trim((string) $stop))
            ->filter(function ($stop) {
                $normalized = mb_strtolower($stop);

                return $normalized !== '' && !in_array($normalized, [
                    '-',
                    '--',
                    '---',
                    'n/a',
                    'na',
                    'non defini',
                    'non défini',
                    'inconnu',
                    'unknown',
                    'null',
                ], true);
            })
            ->values()
            ->all();
    }

    private function findRule(string $vehicleCode, string $serviceCode): ?VehiclePriceRule
    {
        return VehiclePriceRule::query()
            ->where('active', 1)
            ->where('vehicle_type', $vehicleCode)
            ->where(function ($query) use ($serviceCode) {
                $query->where('service_type', $serviceCode)
                    ->orWhereNull('service_type');
            })
            ->orderByRaw('service_type = ? desc', [$serviceCode])
            ->first()
            ?: VehiclePriceRule::query()
                ->where('active', 1)
                ->where('vehicle_type', $vehicleCode)
                ->first();
    }

    private function normalizeVehicleType(?string $vehicleType, ?int $pax): string
    {
        $value = mb_strtolower((string) $vehicleType);

        if (str_contains($value, '59') || str_contains($value, '60') || ($pax && $pax >= 58)) {
            return 'COACH_60';
        }

        if (str_contains($value, '55') || str_contains($value, '56') || str_contains($value, '53') || str_contains($value, '54') || str_contains($value, 'tourismo') || str_contains($value, 'temsa') || ($pax && $pax >= 50)) {
            return 'COACH_55';
        }

        if (str_contains($value, '45') || str_contains($value, '43') || str_contains($value, 'otocar') || str_contains($value, 'otokar') || str_contains($value, 'bova') || ($pax && $pax >= 40)) {
            return 'COACH_45';
        }

        if (str_contains($value, 'coach') || str_contains($value, 'autocar') || str_contains($value, 'bus') || str_contains($value, '37') || str_contains($value, '33') || ($pax && $pax >= 30)) {
            return 'COACH_50';
        }

        if (str_contains($value, '29') || str_contains($value, '30') || str_contains($value, 'iveco') || str_contains($value, 'daily') || ($pax && $pax >= 24)) {
            return 'MINIBUS_30';
        }

        if (str_contains($value, 'sprinter') || str_contains($value, 'minibus') || str_contains($value, '19') || str_contains($value, '22') || str_contains($value, '16') || ($pax && $pax > 8)) {
            return 'SPRINTER_19';
        }

        if (str_contains($value, 'class v') || str_contains($value, 'classe v')) {
            return 'CLASS_V';
        }

        if (str_contains($value, 'vito') || str_contains($value, 'van') || ($pax && $pax <= 8 && $pax > 4)) {
            return 'VAN_8';
        }

        if (str_contains($value, 'sedan') || str_contains($value, 'class e') || str_contains($value, 'class s') || str_contains($value, 'berline') || str_contains($value, 'clio') || str_contains($value, 'peugeot') || str_contains($value, 'toyota') || ($pax && $pax <= 4)) {
            return 'SEDAN_4';
        }

        return 'SPRINTER_19';
    }

    private function normalizeServiceType(?string $serviceType): string
    {
        $value = mb_strtolower((string) $serviceType);

        if (str_contains($value, 'dispo') || str_contains($value, 'mise') || str_contains($value, 'jour') || str_contains($value, 'tour') || str_contains($value, 'panoramic')) {
            return 'dispo';
        }

        return 'transfer';
    }

    private function nightHours(TalepDay $day): float
    {
        if (!$day->start_time) {
            return 0.0;
        }

        try {
            $time = Carbon::parse($day->start_time);
            $hour = (int) $time->format('H');
            return ($hour < 7 || $hour >= 21) ? 1.0 : 0.0;
        } catch (\Throwable $e) {
            return 0.0;
        }
    }
}
