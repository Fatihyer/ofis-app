<?php

namespace App\Services\Pricing;

use App\Models\Talep;
use App\Models\TalepDay;
use App\Services\AiPricingClient;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class TalepAiPricingService
{
    public function __construct(private AiPricingClient $client)
    {
    }

    public function calculate(Talep $talep): array
    {
        $talep->loadMissing('days');

        if ($talep->days->isEmpty()) {
            throw new RuntimeException('Aucune opération disponible pour la suggestion IA.');
        }

        $modelVersion = null;
        $suggestions = $talep->days->map(function (TalepDay $day) use ($talep, &$modelVersion) {
            $durationSeconds = $day->traffic_duration_seconds ?: $day->duration_seconds;
            $parking = $day->parking_amount ?? $day->parking;
            $prediction = $this->client->predict([
                'service_date' => optional($day->service_date)->format('Y-m-d'),
                'start_time' => $day->start_time,
                'end_time' => $day->end_time,
                'service_type' => $day->service_type ?: $talep->service_type,
                'vehicle_type' => $day->vehicle_type ?: $talep->vehicle_type,
                'pax' => $day->pax ?: $talep->total_pax,
                'distance_km' => $day->distance_meters !== null ? (float) $day->distance_meters / 1000 : null,
                'duration_hours' => $durationSeconds !== null ? (float) $durationSeconds / 3600 : null,
                'toll_amount' => $day->toll_amount,
                'fuel_amount' => $day->fuel_amount,
                'overnight_amount' => $day->decoucher,
                'parking_amount' => $parking,
                'checkpoint_amount' => $day->checkpoint,
            ]);

            $modelVersion = $prediction['model_version'] ?? $modelVersion;

            return [
                'day_id' => $day->id,
                'day_number' => $day->day_number,
                'service_date' => optional($day->service_date)->format('d/m/Y'),
                'suggested_price' => (float) $prediction['suggested_price'],
                'currency' => $prediction['currency'] ?? 'EUR',
            ];
        })->values();

        $total = round((float) $suggestions->sum('suggested_price'), 2);

        DB::transaction(function () use ($talep, $suggestions, $total, $modelVersion) {
            foreach ($suggestions as $suggestion) {
                TalepDay::whereKey($suggestion['day_id'])->update([
                    'ai_suggested_price' => $suggestion['suggested_price'],
                ]);
            }

            $talep->update([
                'ai_suggested_total' => $total,
                'ai_suggested_at' => now(),
                'ai_model_version' => $modelVersion,
            ]);
        });

        return [
            'message' => 'Suggestion calculée à partir des tarifs administrateur précédents.',
            'total' => $total,
            'currency' => $talep->currency ?? 'EUR',
            'operations' => $suggestions->all(),
            'model_version' => $modelVersion,
            'warning' => 'Suggestion indicative: validation par un administrateur obligatoire.',
        ];
    }
}

