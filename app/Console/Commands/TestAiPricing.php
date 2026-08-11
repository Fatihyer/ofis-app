<?php

namespace App\Console\Commands;

use App\Services\AiPricingClient;
use Illuminate\Console\Command;

class TestAiPricing extends Command
{
    protected $signature = 'pricing:ai-test';

    protected $description = 'Test the local AI pricing API without changing application data';

    public function handle(AiPricingClient $client): int
    {
        $this->line(json_encode($client->health(), JSON_UNESCAPED_SLASHES));
        $result = $client->predict([
            'service_date' => now()->addMonth()->toDateString(),
            'service_type' => 'transfer',
            'vehicle_type' => 'autocar',
            'pax' => 45,
            'distance_km' => 120,
            'duration_hours' => 3,
            'toll_amount' => 40,
            'fuel_amount' => 90,
        ]);
        $this->line(json_encode($result, JSON_UNESCAPED_SLASHES));

        return self::SUCCESS;
    }
}

