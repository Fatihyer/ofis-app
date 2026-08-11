<?php

namespace App\Services;

use App\Models\Transfer;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class HermesEngineAlertService
{
    public function alerts(bool $fresh = false): array
    {
        if ($fresh) {
            return $this->buildAlerts();
        }

        $bucket = Carbon::now('Europe/Paris')->format('YmdH') . floor(Carbon::now('Europe/Paris')->minute / 15);
        return Cache::remember('hermes_engine_alerts_v2_' . $bucket, 900, function () {
            return $this->buildAlerts();
        });
    }

    public function messages(bool $fresh = false): array
    {
        return array_map(function ($alert) {
            return is_array($alert) ? ($alert['message'] ?? '') : (string) $alert;
        }, $this->alerts($fresh));
    }

    private function buildAlerts(): array
    {
        $now = Carbon::now('Europe/Paris');

        // Aktif saatler dışında (05:00-23:30) uyarı kontrolü yapma
        $hour = $now->hour + $now->minute / 60;
        if ($hour < 5.0 || $hour >= 23.5) {
            return [];
        }

        if ($limitMessage = HermesApiService::dailyLimitMessage()) {
            return [[
                'id' => 'hermes-api-daily-limit',
                'message' => $limitMessage,
            ]];
        }

        $dayStart = $now->copy()->startOfDay();
        $dayEnd = $now->copy()->endOfDay();

        $transfersToCheck = Transfer::with(['vehicule', 'driver', 'servicetype', 'missionr'])
            ->whereBetween('start_date', [$dayStart, $dayEnd])
            ->where(function ($q) {
                $q->whereNull('conge')->orWhere('conge', 0);
            })
            ->where(function ($q) {
                $q->whereNull('status_id')->orWhere('status_id', '!=', 1);
            })
            ->whereHas('vehicule', function ($q) {
                $q->whereNotNull('real')
                  ->whereNotNull('hermes_uid');
            })
            ->orderBy('start_date')
            ->get();

        if ($transfersToCheck->isEmpty()) {
            return [];
        }

        try {
            $units = app(HermesApiService::class)->get('/units');
        } catch (\Throwable $e) {
            Log::warning('Hermes global engine alert check failed', ['error' => $e->getMessage()]);
            return [];
        }

        if (HermesApiService::isDailyLimitPayload($units)) {
            return [[
                'id' => 'hermes-api-daily-limit',
                'message' => data_get($units, 'error.message', 'Hermes: limite quotidienne API atteinte.'),
            ]];
        }

        if (HermesApiService::isUnavailablePayload($units)) {
            Log::warning('Hermes engine alert skipped: API unavailable', [
                'error' => data_get($units, 'error.message'),
            ]);
            return [];
        }

        if (!is_array($units)) {
            return [];
        }

        $unitStatusByUid = collect($units)
            ->filter(fn ($unit) => !empty($unit['uid']))
            ->mapWithKeys(function ($unit) {
                $statusCode = data_get($unit, 'last_position.status.status');
                return [$unit['uid'] => [
                    'code' => is_numeric($statusCode) ? (int) $statusCode : null,
                    'label' => data_get($unit, 'last_position.status.label', 'Statut inconnu'),
                    'date' => data_get($unit, 'last_position.date'),
                ]];
            });

        $messages = [];
        $trackInfoByUid = [];
        $hermes = app(HermesApiService::class);

        foreach ($transfersToCheck as $transfer) {
            $departTime = Carbon::parse($transfer->start_date, 'Europe/Paris');
            $controlTime = $transfer->ofis_start
                ? Carbon::parse($transfer->ofis_start, 'Europe/Paris')
                : $departTime->copy()->subHour();
            $alertEndTime = $departTime->copy()->addMinutes(15);

            if ($controlTime->greaterThan($now) || $alertEndTime->lessThan($now)) {
                continue;
            }

            $engineStartedCacheKey = 'hermes_engine_started_transfer_' . $transfer->id;

            if ($transfer->missionr && $transfer->missionr->hareket) {
                Cache::forever($engineStartedCacheKey, true);
                continue;
            }

            if (Cache::get($engineStartedCacheKey)) {
                continue;
            }

            $vehicule = $transfer->vehicule;
            $status = $unitStatusByUid->get($vehicule->hermes_uid);
            $isRunning = $status && in_array($status['code'], [1, 2, 3], true);

            if ($isRunning) {
                Cache::forever($engineStartedCacheKey, true);
                continue;
            }

            if (!array_key_exists($vehicule->hermes_uid, $trackInfoByUid)) {
                try {
                    $trackInfoByUid[$vehicule->hermes_uid] = $hermes->get('/units/' . $vehicule->hermes_uid . '/track-info');
                } catch (\Throwable $e) {
                    Log::warning('Hermes track-info check failed', [
                        'transfer_id' => $transfer->id,
                        'hermes_uid' => $vehicule->hermes_uid,
                        'error' => $e->getMessage(),
                    ]);
                    $trackInfoByUid[$vehicule->hermes_uid] = null;
                }
            }

            if ($this->hasStartedInWindow($trackInfoByUid[$vehicule->hermes_uid], $controlTime, $alertEndTime)) {
                Cache::forever($engineStartedCacheKey, true);
                continue;
            }

            if (!$status || $status['code'] === null) {
                Log::info('Hermes engine alert skipped: unavailable unit status', [
                    'transfer_id' => $transfer->id,
                    'hermes_uid' => $vehicule->hermes_uid,
                ]);
                continue;
            }

            $vehicleName = trim(($vehicule->plaka ? $vehicule->plaka . ' - ' : '') . ($vehicule->name ?? 'Véhicule'));
            $driverName = optional($transfer->driver)->name ?: 'chauffeur non défini';
            $serviceName = optional($transfer->servicetype)->name ?: 'service';
            $statusLabel = $status['label'] ?? 'statut Hermes indisponible';

            $messages[] = [
                'id' => 'transfer-' . $transfer->id . '-control-' . $controlTime->format('YmdHi'),
                'message' => "Hermes alerte: {$vehicleName} ne semble pas démarré ({$statusLabel}) pour le transfert #{$transfer->id} à "
                    . Carbon::parse($transfer->start_date)->format('H:i')
                    . " / en route prévu {$controlTime->format('H:i')} ({$driverName}, {$serviceName}).",
            ];
        }

        foreach ($transfersToCheck as $transfer) {
            if (!$transfer->end_date) {
                continue;
            }

            if ($transfer->missionr && $transfer->missionr->finish) {
                continue;
            }

            $endTime = Carbon::parse($transfer->end_date, 'Europe/Paris');
            $alertStart = $endTime->copy()->addMinutes(5);
            $alertEnd = $endTime->copy()->endOfDay();

            if ($now->lessThan($alertStart) || $now->greaterThan($alertEnd)) {
                continue;
            }

            $vehicule = $transfer->vehicule;
            if (!$vehicule || !$vehicule->hermes_uid) {
                continue;
            }

            $status = $unitStatusByUid->get($vehicule->hermes_uid);
            if (!$status || $status['code'] === null) {
                continue;
            }

            $vehicleName = trim(($vehicule->plaka ? $vehicule->plaka . ' - ' : '') . ($vehicule->name ?? 'Véhicule'));
            $driverName = optional($transfer->driver)->name ?: 'chauffeur non défini';
            $serviceName = optional($transfer->servicetype)->name ?: 'service';
            $statusLabel = $status['label'] ?? 'statut Hermes indisponible';
            $isRunning = in_array($status['code'], [1, 2, 3], true);

            if (!$isRunning) {
                continue;
            }

            if ($this->vehicleIsReusedAfterTransfer($transfer, $endTime, $now)) {
                continue;
            }

            $messages[] = [
                'id' => 'transfer-' . $transfer->id . '-end-not-finished-' . $endTime->format('YmdHi') . '-' . floor($now->minute / 15),
                'message' => "Fin prévue dépassée: le transfert #{$transfer->id} devait être terminé à {$endTime->format('H:i')}, "
                    . "mais Hermes indique que le véhicule est encore actif ({$statusLabel}) et la mission n’est pas clôturée"
                    . " ({$driverName}, {$vehicleName}, {$serviceName}).",
            ];
        }

        return array_slice($messages, 0, 12);
    }

    private function vehicleIsReusedAfterTransfer(Transfer $transfer, Carbon $endTime, Carbon $now): bool
    {
        if (!$transfer->vehicule_id) {
            return false;
        }

        $windowStart = $endTime->copy()->subMinutes(5);
        $windowEnd = $now->copy()->addMinutes(30);

        return Transfer::query()
            ->where('id', '!=', $transfer->id)
            ->where('vehicule_id', $transfer->vehicule_id)
            ->where(function ($q) {
                $q->whereNull('conge')->orWhere('conge', 0);
            })
            ->where(function ($q) {
                $q->whereNull('status_id')->orWhere('status_id', '!=', 1);
            })
            ->where(function ($q) use ($windowStart, $windowEnd, $now) {
                $q->whereBetween('start_date', [$windowStart, $windowEnd])
                    ->orWhereBetween('ofis_start', [$windowStart, $windowEnd])
                    ->orWhere(function ($activeQuery) use ($now) {
                        $activeQuery->where('start_date', '<=', $now)
                            ->where('end_date', '>=', $now->copy()->subMinutes(15));
                    });
            })
            ->exists();
    }

    private function hasStartedInWindow($trackInfo, Carbon $controlTime, Carbon $alertEndTime): bool
    {
        if (!is_array($trackInfo)) {
            return false;
        }

        foreach ($trackInfo as $dayRow) {
            if (!is_array($dayRow)) {
                continue;
            }

            if (isset($dayRow['day']) && $dayRow['day'] !== $controlTime->toDateString() && $dayRow['day'] !== $alertEndTime->toDateString()) {
                continue;
            }

            if ($this->trackDayRowShowsActivity($dayRow, $controlTime, $alertEndTime)) {
                return true;
            }
        }

        return false;
    }

    private function trackDayRowShowsActivity(array $dayRow, Carbon $controlTime, Carbon $alertEndTime): bool
    {
        foreach (($dayRow['events'] ?? []) as $event) {
            if (!is_array($event)) {
                continue;
            }

            $statusCode = $event['status'] ?? null;
            if (!is_numeric($statusCode) || !in_array((int) $statusCode, [1, 2, 3], true)) {
                continue;
            }

            if (empty($event['date'])) {
                return true;
            }

            $eventTime = Carbon::parse($event['date'])->setTimezone('Europe/Paris');
            if ($eventTime->betweenIncluded($controlTime, $alertEndTime)) {
                return true;
            }
        }

        $distance = $dayRow['distance'] ?? 0;
        $duration = $dayRow['duration'] ?? 0;
        if ((!is_numeric($distance) || (float) $distance <= 0) && (!is_numeric($duration) || (int) $duration <= 0)) {
            return false;
        }

        $day = $dayRow['day'] ?? $controlTime->toDateString();
        $begin = $this->minuteOfDayToParisCarbon($day, $dayRow['beginDay'] ?? null);
        $end = $this->minuteOfDayToParisCarbon($day, $dayRow['endDay'] ?? null);

        return $begin && $end && $begin->lessThanOrEqualTo($alertEndTime) && $end->greaterThanOrEqualTo($controlTime);
    }

    private function minuteOfDayToParisCarbon(string $day, $minuteOfDay): ?Carbon
    {
        if ($minuteOfDay === null || !is_numeric($minuteOfDay)) {
            return null;
        }

        $minute = (int) $minuteOfDay;

        return Carbon::parse($day, 'Europe/Paris')->startOfDay()->addMinutes($minute);
    }
}
