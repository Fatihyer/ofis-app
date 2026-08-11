<?php

namespace App\Console\Commands;

use App\Services\HermesApiService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class HermesArchiveDailyStats extends Command
{
    protected $signature = 'hermes:archive-daily {--day=} {--force : Recharger Hermes même si la journée est déjà archivée}';
    protected $description = 'Archive Hermes daily track-info stats into DB for all vehicles';

    public function handle(HermesApiService $hermes): int
    {
        $tz = 'Europe/Paris';

        // ✅ Default: BUGÜN (23:50’de çalışacağı için)
        $dayKey = $this->option('day')
            ?: Carbon::today($tz)->toDateString();

        $vehicles = DB::table('vehicules')
            ->whereNotNull('hermes_uid')
            ->select(['id','name','plaka','hermes_uid'])
            ->orderBy('id')
            ->get();

        $saved = 0;
        $skipped = 0;
        $missing = 0;
        $errors = 0;
        $force = (bool) $this->option('force');

        foreach ($vehicles as $v) {
            try {
                if (!$force && DB::table('hermes_daily_stats')->where('vehicule_id', $v->id)->where('day', $dayKey)->exists()) {
                    $skipped++;
                    $this->line("SKIP vehicule#{$v->id} {$v->name} day={$dayKey} déjà archivé");
                    continue;
                }

                $track = $hermes->get("/units/{$v->hermes_uid}/track-info");

                if (HermesApiService::isDailyLimitPayload($track)) {
                    $this->warn(data_get($track, 'error.message', 'Limite quotidienne Hermes atteinte.'));
                    break;
                }

                $dayRow = $this->findDayRowAnyShape($track, $dayKey);

                if (!$dayRow) {
                    $missing++;
                    $this->line("MISS vehicule#{$v->id} {$v->name} ({$v->hermes_uid}) day={$dayKey}");
                    continue;
                }

                // ✅ created_at sadece insertte set edilsin
                DB::table('hermes_daily_stats')->updateOrInsert(
                    [
                        'vehicule_id' => $v->id,
                        'day' => $dayKey,
                    ],
                    [
                        'distance_km'   => $dayRow['distance'] ?? null,
                        'duration_sec'  => isset($dayRow['duration']) ? (int)$dayRow['duration'] : null,
                        'begin_minute'  => isset($dayRow['beginDay']) ? (int)$dayRow['beginDay'] : null,
                        'end_minute'    => isset($dayRow['endDay']) ? (int)$dayRow['endDay'] : null,
                        'max_speed'     => isset($dayRow['maxSpeed']) ? (int)$dayRow['maxSpeed'] : null,
                        'raw'           => json_encode($dayRow, JSON_UNESCAPED_UNICODE),
                        'updated_at'    => now(),
                    ] + $this->createdAtIfNew('hermes_daily_stats', [
                        'vehicule_id' => $v->id,
                        'day' => $dayKey,
                    ])
                );

                $saved++;
                $this->info("OK   vehicule#{$v->id} {$v->name} day={$dayKey} km=" . ($dayRow['distance'] ?? 'null'));
            } catch (\Throwable $e) {
                $errors++;
                $this->error("ERR  vehicule#{$v->id} {$v->name} ({$v->hermes_uid}) => " . $e->getMessage());
            }
        }

        $this->newLine();
        $this->info("DONE day={$dayKey} saved={$saved} skipped={$skipped} missing={$missing} errors={$errors}");

        return Command::SUCCESS;
    }

    /**
     * Track-info bazen nested gelebilir diye: her şekilden day row bulur
     */
    private function findDayRowAnyShape($track, string $dayKey): ?array
    {
        $rows = $this->extractTrackDayRows($track);

        foreach ($rows as $r) {
            if (($r['day'] ?? null) === $dayKey) {
                return $r;
            }
        }

        return null;
    }

    private function extractTrackDayRows($track): array
    {
        $rows = [];
        $stack = [$track];

        while (!empty($stack)) {
            $cur = array_pop($stack);
            if (!is_array($cur)) continue;

            // day row gibi mi?
            if (isset($cur['day']) && (
                array_key_exists('distance', $cur) ||
                array_key_exists('duration', $cur) ||
                array_key_exists('beginDay', $cur) ||
                array_key_exists('endDay', $cur) ||
                array_key_exists('events', $cur)
            )) {
                $rows[] = $cur;
                continue;
            }

            foreach ($cur as $v) {
                if (is_array($v)) $stack[] = $v;
            }
        }

        return $rows;
    }

    /**
     * updateOrInsert created_at’i her seferinde değiştirmesin diye:
     * kayıt yoksa created_at ekler, varsa eklemez.
     */
    private function createdAtIfNew(string $table, array $where): array
    {
        $exists = DB::table($table)->where($where)->exists();
        return $exists ? [] : ['created_at' => now()];
    }
}
