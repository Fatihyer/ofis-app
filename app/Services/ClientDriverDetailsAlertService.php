<?php

namespace App\Services;

use App\Models\Option;
use App\Models\Transfer;
use App\Models\UserAttendance;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

class ClientDriverDetailsAlertService
{
    public function alerts(bool $fresh = false, ?User $viewer = null): array
    {
        if ($fresh || $viewer) {
            return $this->buildAlerts($viewer);
        }

        return Cache::remember('client_driver_details_alerts_' . Carbon::now('Europe/Paris')->format('YmdHi'), 60, function () {
            return $this->buildAlerts();
        });
    }

    public function messages(bool $fresh = false, ?User $viewer = null): array
    {
        return array_map(function ($alert) {
            return $alert['message'] ?? '';
        }, $this->alerts($fresh, $viewer));
    }

    private function buildAlerts(?User $viewer = null): array
    {
        $now = Carbon::now('Europe/Paris');
        $today = $now->copy()->startOfDay();
        $tomorrow = $today->copy()->addDay();
        $afterHour = (int) (Option::where('name', 'kaptanDayAfterTime')->value('value') ?? 17);
        $afterHour = max(0, min(23, $afterHour));

        $ranges = [
            ['label' => "aujourd'hui", 'start' => $today->copy(), 'end' => $today->copy()->endOfDay()],
        ];

        if ($now->hour >= $afterHour) {
            $ranges[] = ['label' => 'demain', 'start' => $tomorrow->copy(), 'end' => $tomorrow->copy()->endOfDay()];
        }

        $operationAttendanceName = optional(UserAttendance::where('operation_user_id', '>', 0)->latest()->first())->permanence_name;
        $alerts = [];
        foreach ($ranges as $range) {
            $relations = ['driver', 'vehicule', 'servicetype', 'status', 'post.acente', 'post.user'];
            if (Schema::hasColumn('transfers', 'second_driver_id')) {
                $relations[] = 'secondDriver';
            }

            $transfers = Transfer::with($relations)
                ->whereBetween('start_date', [$range['start'], $range['end']])
                ->where(function ($q) {
                    $q->whereNull('conge')->orWhere('conge', 0);
                })
                ->where(function ($q) {
                    $q->whereNull('status_id')->orWhere('status_id', '!=', 1);
                })
                ->where(function ($q) {
                    $q->where(function ($sub) {
                        $sub->whereNull('client_status_id')->orWhere('client_status_id', 0);
                    })
                    ->orWhereNull('ofis_start')
                    ->orWhereNull('driver_app_confirmed_at');
                    if (Schema::hasColumn('transfers', 'second_driver_id') && Schema::hasColumn('transfers', 'second_driver_app_confirmed_at')) {
                        $q->orWhere(function ($second) {
                            $second->whereNotNull('second_driver_id')->whereNull('second_driver_app_confirmed_at');
                        });
                    }
                })
                ->orderBy('start_date')
                ->limit(8)
                ->get();

            foreach ($transfers as $transfer) {
                $fileUserId = optional($transfer->post)->user_id;
                if ($viewer && !app(NotificationAudienceService::class)->sharesGroupWithUser($viewer, $fileUserId)) {
                    continue;
                }

                $time = Carbon::parse($transfer->start_date, 'Europe/Paris')->format('H:i');
                $driverName = optional($transfer->driver)->name ?: 'chauffeur non défini';
                $vehicleName = optional($transfer->vehicule)->plaka ?: optional($transfer->vehicule)->name ?: 'véhicule non défini';
                $serviceName = optional($transfer->servicetype)->name ?: 'service';
                $agencyName = optional(optional($transfer->post)->acente)->name;
                $fileUserName = optional(optional($transfer->post)->user)->name;
                $contextParts = [
                    'agency' => $agencyName,
                    'file_user' => $fileUserName,
                    'operation' => $operationAttendanceName,
                ];
                $alertContext = collect([
                    $agencyName,
                    $fileUserName,
                    $operationAttendanceName ? 'Opération: ' . $operationAttendanceName : null,
                ])->filter()->implode(', ');

                $issues = [];
                if (!$transfer->client_status_id) {
                    $issues[] = 'Détails chauffeur non envoyés au client';
                }
                if (!$transfer->ofis_start) {
                    $issues[] = 'En route: Non défini';
                }
                if (($transfer->driver_app_refused_at ?? null) && Schema::hasColumn('transfers', 'driver_app_refused_at')) {
                    $issues[] = 'Service refusé par le chauffeur';
                } elseif (($transfer->driver_app_reconfirm_required_at ?? null) && Schema::hasColumn('transfers', 'driver_app_reconfirm_required_at')) {
                    $issues[] = 'Service modifié à reconfirmer par le chauffeur';
                } elseif (!$transfer->driver_app_confirmed_at) {
                    $issues[] = 'Mission non confirmée par le chauffeur';
                }
                if (Schema::hasColumn('transfers', 'second_driver_id')
                    && ($transfer->second_driver_id ?? null)
                    && Schema::hasColumn('transfers', 'second_driver_app_confirmed_at')
                    && !$transfer->second_driver_app_confirmed_at) {
                    $secondDriverName = optional($transfer->secondDriver)->name ?: '2e chauffeur';
                    if (($transfer->second_driver_app_refused_at ?? null) && Schema::hasColumn('transfers', 'second_driver_app_refused_at')) {
                        $issues[] = 'Service refusé par le 2e chauffeur (' . $secondDriverName . ')';
                    } elseif (($transfer->second_driver_app_reconfirm_required_at ?? null) && Schema::hasColumn('transfers', 'second_driver_app_reconfirm_required_at')) {
                        $issues[] = 'Service modifié à reconfirmer par le 2e chauffeur (' . $secondDriverName . ')';
                    } else {
                        $issues[] = 'Mission non confirmée par le 2e chauffeur (' . $secondDriverName . ')';
                    }
                }

                if (empty($issues)) {
                    continue;
                }

                $alerts[] = [
                    'id' => 'operation-warning-' . $transfer->id . '-' . Carbon::parse($transfer->start_date, 'Europe/Paris')->format('YmdHi') . '-' . md5(implode('|', $issues)),
                    'message' => implode(' + ', $issues) . ": transfert #{$transfer->id} {$range['label']} à {$time} ({$driverName}, {$vehicleName}, {$serviceName}).",
                    'issues' => $issues,
                    'schedule_text' => "transfert #{$transfer->id} {$range['label']} à {$time}",
                    'transfer_context' => "({$driverName}, {$vehicleName}, {$serviceName}).",
                    'url' => route('transfers.show', $transfer->id, false),
                    'transfer_id' => $transfer->id,
                    'context' => $alertContext,
                    'context_parts' => $contextParts,
                    'file_user_id' => $fileUserId,
                ];
            }
        }

        return array_slice($alerts, 0, 12);
    }
}
