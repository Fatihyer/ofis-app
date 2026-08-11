<?php

namespace App\Console\Commands;

use App\Services\WhatsApp\WhatsAppGroupManager;
use Illuminate\Console\Command;

class WhatsAppSyncGroupsCommand extends Command
{
    protected $signature = 'whatsapp:sync-groups';

    protected $description = 'Synchronise les groupes WhatsApp depuis le gateway WPPConnect.';

    public function handle(WhatsAppGroupManager $manager): int
    {
        $count = $manager->syncGroups();
        $this->info($count . ' groupes synchronisés.');

        return self::SUCCESS;
    }
}
