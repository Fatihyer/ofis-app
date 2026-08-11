<?php

namespace App\Console\Commands;

use App\Models\WhatsAppGroup;
use App\Services\WhatsApp\WhatsAppGroupManager;
use Illuminate\Console\Command;

class WhatsAppStatusCommand extends Command
{
    protected $signature = 'whatsapp:status';

    protected $description = 'Affiche le statut du gateway WhatsApp.';

    public function handle(WhatsAppGroupManager $manager): int
    {
        try {
            $status = $manager->status();
        } catch (\Throwable $e) {
            $this->error('WhatsApp Gateway: OFFLINE');
            $this->line('Erreur: ' . $e->getMessage());
            return self::FAILURE;
        }

        $this->line('WhatsApp Gateway: ' . strtoupper($status['status'] ?? 'unknown'));
        $this->line('WhatsApp: ' . strtoupper($status['whatsapp'] ?? 'unknown'));
        $this->line('Session: ' . ($status['session'] ?? '-'));
        $this->line('Groups: ' . WhatsAppGroup::count());
        $this->line('Active monitoring groups: ' . WhatsAppGroup::where('is_active', true)->count());

        return self::SUCCESS;
    }
}
