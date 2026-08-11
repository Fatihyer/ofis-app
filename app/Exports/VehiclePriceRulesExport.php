<?php

namespace App\Exports;

use App\Models\VehiclePriceRule;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithTitle;

class VehiclePriceRulesExport implements WithMultipleSheets
{
    public function sheets(): array
    {
        return [
            new VehiclePriceRulesSheet(),
            new VehiclePriceDateAdjustmentsSheet(),
        ];
    }
}

class VehiclePriceRulesSheet implements FromArray, WithHeadings, WithTitle, ShouldAutoSize
{
    public function title(): string
    {
        return 'Tarifs';
    }

    public function headings(): array
    {
        return [
            'ID',
            'Vehicule',
            'Service',
            'Base',
            'Km inclus',
            'Heures incluses',
            'Prix km extra',
            'Prix heure extra',
            'Prix heure nuit',
            'Minimum',
            'Repas chauffeur',
            'Hotel chauffeur',
            'Marge %',
            'TVA %',
            'Actif',
            'Cree le',
            'Modifie le',
        ];
    }

    public function array(): array
    {
        return VehiclePriceRule::orderBy('vehicle_type')
            ->orderBy('service_type')
            ->get()
            ->map(function (VehiclePriceRule $rule) {
                return [
                    $rule->id,
                    $rule->vehicle_type,
                    $rule->service_type,
                    (float) $rule->base_rate,
                    (int) $rule->included_km,
                    (float) $rule->included_hours,
                    (float) $rule->extra_km_rate,
                    (float) $rule->extra_hour_rate,
                    (float) $rule->night_extra_hour_rate,
                    (float) $rule->minimum_charge,
                    (float) $rule->driver_meal_cost,
                    (float) $rule->driver_hotel_cost,
                    (float) $rule->default_margin_percent,
                    (float) $rule->vat_rate,
                    $rule->active ? 'Oui' : 'Non',
                    optional($rule->created_at)->format('d/m/Y H:i'),
                    optional($rule->updated_at)->format('d/m/Y H:i'),
                ];
            })
            ->values()
            ->all();
    }
}

class VehiclePriceDateAdjustmentsSheet implements FromArray, WithHeadings, WithTitle, ShouldAutoSize
{
    public function title(): string
    {
        return 'Dates';
    }

    public function headings(): array
    {
        return [
            'ID',
            'Libelle',
            'Vehicule',
            'Service',
            'Debut',
            'Fin',
            'Sens',
            'Type',
            'Valeur',
            'Actif',
            'Cree le',
            'Modifie le',
        ];
    }

    public function array(): array
    {
        return DB::table('vehicle_price_date_adjustments')
            ->orderByDesc('start_date')
            ->orderBy('vehicle_type')
            ->get()
            ->map(function ($adjustment) {
                return [
                    $adjustment->id,
                    $adjustment->label,
                    $adjustment->vehicle_type ?: 'Tous',
                    $adjustment->service_type ?: 'Tous',
                    $adjustment->start_date,
                    $adjustment->end_date,
                    ($adjustment->direction ?? 'increase') === 'discount' ? 'Remise' : 'Majoration',
                    $adjustment->adjustment_type === 'percent' ? '%' : 'EUR',
                    (float) $adjustment->adjustment_value,
                    !empty($adjustment->active) ? 'Oui' : 'Non',
                    $adjustment->created_at,
                    $adjustment->updated_at,
                ];
            })
            ->values()
            ->all();
    }
}
