<?php

// app/Console/Commands/GenerateTransferTokens.php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Transfer;
use Illuminate\Support\Str;

class GenerateTransferTokens extends Command
{
    protected $signature = 'transfers:generate-tokens';
    protected $description = 'Boş olan confirmation_token alanları için UUID üretir';

    public function handle()
    {
        $count = 0;

        Transfer::whereNull('confirmation_token')->chunk(100, function ($transfers) use (&$count) {
            foreach ($transfers as $transfer) {
                $transfer->confirmation_token = Str::uuid();
                $transfer->save();
                $count++;
            }
        });

        $this->info("✅ Toplam {$count} transfer için token üretildi.");
    }
}
