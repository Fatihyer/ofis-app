<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ExportPricingTrainingData extends Command
{
    protected $signature = 'pricing:export-training
        {--output=/home/ofis/ai-pricing/data/pricing_training.csv}';

    protected $description = 'Export anonymized priced demand days for the local AI pricing model';

    public function handle(): int
    {
        $output = $this->option('output');
        $directory = dirname($output);

        if (!is_dir($directory) && !mkdir($directory, 0750, true) && !is_dir($directory)) {
            $this->error("Cannot create {$directory}");
            return self::FAILURE;
        }

        $handle = fopen($output, 'wb');
        if ($handle === false) {
            $this->error("Cannot write {$output}");
            return self::FAILURE;
        }

        $columns = [
            'service_type', 'vehicle_type', 'pax', 'distance_km', 'duration_hours',
            'toll_amount', 'fuel_amount', 'overnight_amount', 'parking_amount',
            'checkpoint_amount', 'service_month', 'service_weekday', 'target_price',
        ];
        fputcsv($handle, $columns);
        $written = 0;

        DB::table('talep_days')
            ->select([
                'id', 'service_date', 'service_type', 'vehicle_type', 'pax',
                'distance_meters', 'duration_seconds', 'toll_amount', 'fuel_amount',
                'decoucher', 'parking_amount', 'parking', 'checkpoint',
                'admin_price', 'final_price',
            ])
            ->where(function ($query) {
                $query->whereNotNull('admin_price')->orWhereNotNull('final_price');
            })
            ->orderBy('id')
            ->chunkById(500, function ($rows) use ($handle, &$written) {
                foreach ($rows as $row) {
                    $date = $row->service_date ? new \DateTimeImmutable($row->service_date) : null;
                    $target = $row->admin_price ?? $row->final_price;

                    fputcsv($handle, [
                        $row->service_type,
                        $row->vehicle_type,
                        $row->pax,
                        $row->distance_meters !== null ? $row->distance_meters / 1000 : null,
                        $row->duration_seconds !== null ? $row->duration_seconds / 3600 : null,
                        $row->toll_amount,
                        $row->fuel_amount,
                        $row->decoucher,
                        $row->parking_amount ?? $row->parking,
                        $row->checkpoint,
                        $date?->format('n'),
                        $date?->format('N') !== null ? ((int) $date->format('N')) - 1 : null,
                        $target,
                    ]);
                    $written++;
                }
            });

        fclose($handle);
        chmod($output, 0640);
        $this->info("Exported {$written} anonymized rows to {$output}");
        return self::SUCCESS;
    }
}
