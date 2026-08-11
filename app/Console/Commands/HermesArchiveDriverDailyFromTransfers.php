<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class HermesArchiveDriverDailyFromTransfers extends Command
{
    protected $signature = 'hermes:archive-driver-daily-from-transfers {--day=}';
    protected $description = 'Attach driver_id (acente_id) to vehicle daily stats using transfers for a given day';

    public function handle(): int
    {
        $tz = 'Europe/Paris';

        // 23:55 çalışacak -> BUGÜN (Paris)
        $dayKey = $this->option('day') ?: Carbon::today($tz)->toDateString();

        $start = Carbon::parse($dayKey, $tz)->startOfDay()->toDateTimeString(); // YYYY-mm-dd 00:00:00
        $end   = Carbon::parse($dayKey, $tz)->endOfDay()->toDateTimeString();   // YYYY-mm-dd 23:59:59

        /**
         * 1) O gün transferlerde geçen vehicule_id -> driver_id eşleşmeleri (distinct)
         * Overlap mantığı: start_date gün içinde OR end_date gün içinde OR gün tamamen arada
         */
        $pairs = DB::table('transfers')
            ->whereNotNull('vehicule_id')
            ->whereNotNull('driver_id')
            ->where('driver_id', '>', 0)
            ->where(function ($q) use ($start, $end) {
                $q->whereBetween('start_date', [$start, $end])
                  ->orWhereBetween('end_date', [$start, $end])
                  ->orWhere(function ($qq) use ($start, $end) {
                      $qq->where('start_date', '<=', $start)
                         ->where('end_date', '>=', $end);
                  });
            })
            ->select(['vehicule_id', 'driver_id'])
            ->distinct()
            ->get();

        // vehicule_id -> driver_id
        $vehToDriver = [];
        $conflicts = 0;

        foreach ($pairs as $p) {
            $vid = (int) $p->vehicule_id;
            $did = (int) $p->driver_id;

            if (isset($vehToDriver[$vid]) && $vehToDriver[$vid] !== $did) {
                // aynı gün aynı araç farklı driver: conflict
                $conflicts++;
                continue; // ilk driver kalsın
            }
            $vehToDriver[$vid] = $did;
        }

        /**
         * 2) Hermes araç günlük stats (hermes_daily_stats) -> driver_daily_stats'e yaz
         * NOT: Transferde driver yoksa acente_id NULL yazılır (kiralık/transfer yok vs)
         */
        $unitStats = DB::table('hermes_daily_stats')
            ->whereDate('day', $dayKey)
            ->select([
                'vehicule_id',
                'distance_km',
                'duration_sec',
                'begin_minute',
                'end_minute',
                'max_speed',
                'raw',
            ])
            ->get();

        if ($unitStats->count() === 0) {
            $this->warn("No hermes_daily_stats found for day={$dayKey}. Run hermes:archive-daily first.");
            return Command::SUCCESS;
        }

        $saved = 0;

        foreach ($unitStats as $s) {
            $vid = (int) $s->vehicule_id;

            $driverId = $vehToDriver[$vid] ?? null; // null olabilir

            $km  = is_numeric($s->distance_km) ? (float)$s->distance_km : 0.0;
            $dur = is_numeric($s->duration_sec) ? (int)$s->duration_sec : 0;

            DB::table('hermes_driver_daily_stats')->updateOrInsert(
                [
                    'vehicule_id' => $vid,
                    'day' => $dayKey,
                ],
                [
                    'acente_id' => $driverId, // NULL olabilir
                    'distance_km' => $km,
                    'driving_sec' => $dur,
                    'begin_minute' => is_numeric($s->begin_minute) ? (int)$s->begin_minute : null,
                    'end_minute' => is_numeric($s->end_minute) ? (int)$s->end_minute : null,
                    'max_speed' => is_numeric($s->max_speed) ? (int)$s->max_speed : null,
                    'raw' => json_encode([
                        'day' => $dayKey,
                        'vehicule_id' => $vid,
                        'driver_id' => $driverId,
                        'source' => 'hermes_daily_stats + transfers(distinct vehicle->driver)',
                        'hermes_daily_stats' => [
                            'distance_km' => $s->distance_km,
                            'duration_sec' => $s->duration_sec,
                            'begin_minute' => $s->begin_minute,
                            'end_minute' => $s->end_minute,
                            'max_speed' => $s->max_speed,
                        ],
                    ], JSON_UNESCAPED_UNICODE),
                    'updated_at' => now(),
                ]
            );

            $saved++;
        }

        $this->info("DONE day={$dayKey} rows_saved={$saved} conflicts={$conflicts} pairs_found={$pairs->count()}");

        return Command::SUCCESS;
    }
}
