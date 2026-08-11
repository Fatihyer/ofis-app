<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class HermesArchiveDriverWorkingStats extends Command
{
    protected $signature = 'hermes:archive-driver-working-stats {--day=}';
    protected $description = 'Aggregate driver working stats from hermes_driver_daily_stats';

    public function handle(): int
    {
        $tz = 'Europe/Paris';

        $dayKey = $this->option('day')
            ?: Carbon::today($tz)->toDateString();

        $rows = DB::table('hermes_driver_daily_stats')
            ->whereDate('day', $dayKey)
            ->select([
                'acente_id',
                'vehicule_id',
                'distance_km',
                'driving_sec',
                'begin_minute',
                'end_minute',
                'max_speed'
            ])
            ->get();

        if ($rows->count() === 0) {
            $this->warn("No hermes_driver_daily_stats found for {$dayKey}");
            return Command::SUCCESS;
        }

        $planningStarts = $this->loadPlanningStartMinutes($dayKey);

        $byDriver = [];

        foreach ($rows as $r) {

            if (!$r->acente_id) {
                continue;
            }

            $did = (int)$r->acente_id;

            $drive    = (int)$r->driving_sec;
            $beginMin = is_numeric($r->begin_minute) ? (int)$r->begin_minute : null;
            $endMin   = is_numeric($r->end_minute)   ? (int)$r->end_minute   : null;

            // --- Gece yarısı carryover tespiti ---
            // Hermes bazen önceki günden açık kalan oturumu yeni günün başından (00:00)
            // itibaren sayar: begin_minute=0 ve driving_sec ≈ end_minute*60.
            if ($beginMin === 0 && $endMin !== null && $drive > 0) {
                $expectedSec = $endMin * 60;
                if (abs($drive - $expectedSec) <= 120) {
                    $actualBegin = $planningStarts[$did] ?? null;
                    if ($actualBegin !== null && $actualBegin < $endMin) {
                        $correctedDrive = max(0, ($endMin - $actualBegin) * 60);
                        $this->warn(
                            "CARRYOVER vehicle#{$r->vehicule_id} driver#{$did} day={$dayKey}: " .
                            "begin=0 drive=" . gmdate('H:i:s', $drive) .
                            " → corrected begin={$actualBegin} drive=" . gmdate('H:i:s', $correctedDrive)
                        );
                        $drive    = $correctedDrive;
                        $beginMin = $actualBegin;
                    } else {
                        $this->warn(
                            "CARRYOVER vehicle#{$r->vehicule_id} driver#{$did} day={$dayKey}: " .
                            "begin=0 drive=" . gmdate('H:i:s', $drive) . " → no planning data, skipped"
                        );
                        continue;
                    }
                }
            }

            // --- Sürüş > amplitude (fiziksel imkansız) ---
            // Sürüş süresi, şoförün bulunuş penceresini (begin→end) aşamaz.
            if ($beginMin !== null && $endMin !== null && $endMin > $beginMin) {
                $amplitudeSec = ($endMin - $beginMin) * 60;
                if ($drive > $amplitudeSec + 300) {
                    $this->warn(
                        "DRIVE>AMPLITUDE vehicle#{$r->vehicule_id} driver#{$did} day={$dayKey}: " .
                        "drive=" . gmdate('H:i:s', $drive) .
                        " amplitude=" . gmdate('H:i:s', $amplitudeSec) .
                        " → capped"
                    );
                    $drive = $amplitudeSec;
                }
            }

            if (!isset($byDriver[$did])) {
                $byDriver[$did] = [
                    'working_sec' => 0,
                    'driving_sec' => 0,
                    'begin_minute' => null,
                    'end_minute' => null,
                    'units' => []
                ];
            }

            $byDriver[$did]['driving_sec'] += $drive;
            $byDriver[$did]['working_sec'] += $drive;

            if ($beginMin !== null) {
                $byDriver[$did]['begin_minute'] =
                    is_null($byDriver[$did]['begin_minute'])
                        ? $beginMin
                        : min($byDriver[$did]['begin_minute'], $beginMin);
            }

            if ($endMin !== null) {
                $byDriver[$did]['end_minute'] =
                    is_null($byDriver[$did]['end_minute'])
                        ? $endMin
                        : max($byDriver[$did]['end_minute'], $endMin);
            }

            $byDriver[$did]['units'][] = [
                'vehicule_id' => $r->vehicule_id,
                'driving_sec' => $drive
            ];
        }

        $saved = 0;

        foreach ($byDriver as $driverId => $agg) {

            DB::table('hermes_driver_daily_working_stats')
                ->updateOrInsert(
                    [
                        'acente_id' => $driverId,
                        'day' => $dayKey
                    ],
                    [
                        'working_sec' => $agg['working_sec'],
                        'driving_sec' => $agg['driving_sec'],
                        'rest_sec' => null,
                        'other_sec' => null,
                        'begin_minute' => $agg['begin_minute'],
                        'end_minute' => $agg['end_minute'],
                        'raw' => json_encode([
                            'day' => $dayKey,
                            'units' => $agg['units']
                        ], JSON_UNESCAPED_UNICODE),
                        'updated_at' => now()
                    ]
                );

            $saved++;
        }

        $this->info("DONE working stats for {$dayKey} drivers_saved={$saved}");

        return Command::SUCCESS;
    }

    /**
     * O gün her şoför için transfer planlamasındaki en erken başlangıç dakikası.
     * Gece yarısı carryover düzeltmesinde gerçek başlangıç saati olarak kullanılır.
     *
     * @return array<int, int>  driver_id → begin_minute
     */
    private function loadPlanningStartMinutes(string $dayKey): array
    {
        $start = Carbon::parse($dayKey)->startOfDay()->toDateTimeString();
        $end   = Carbon::parse($dayKey)->endOfDay()->toDateTimeString();

        $rows = DB::table('transfers')
            ->whereNotNull('driver_id')
            ->where('driver_id', '>', 0)
            ->whereBetween('start_date', [$start, $end])
            ->selectRaw('driver_id, MIN(TIMESTAMPDIFF(MINUTE, DATE(start_date), start_date)) as begin_minute')
            ->groupBy('driver_id')
            ->get();

        $result = [];
        foreach ($rows as $r) {
            $result[(int)$r->driver_id] = (int)$r->begin_minute;
        }
        return $result;
    }
}
