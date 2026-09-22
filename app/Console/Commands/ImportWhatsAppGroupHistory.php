<?php

namespace App\Console\Commands;

use App\Models\WhatsAppGroup;
use App\Services\WhatsApp\WhatsAppGroupManager;
use Illuminate\Console\Command;

class ImportWhatsAppGroupHistory extends Command
{
    protected $signature = 'whatsapp:import-history
                            {--group=* : Grup external_id veya isim parcasi (birden fazla verilebilir)}
                            {--all : Veritabanindaki tum aktif gruplari ice aktar}
                            {--limit=0 : Grup basina en fazla mesaj (0 = sinirsiz)}
                            {--days=0 : Sadece son N gunun mesajlari (0 = hepsi)}';

    protected $description = 'WhatsApp gruplarinin gecmis mesajlarini gateway uzerinden veritabanina aktarir';

    public function handle(WhatsAppGroupManager $manager): int
    {
        $groups = $this->resolveGroups();

        if ($groups->isEmpty()) {
            $this->error('Eslesen grup bulunamadi. Once "php artisan whatsapp:sync-groups" veya arayuzden senkronizasyon gerekir.');

            return self::FAILURE;
        }

        $since = ((int) $this->option('days')) > 0
            ? now()->subDays((int) $this->option('days'))->timestamp
            : null;

        $total = 0;

        foreach ($groups as $group) {
            $this->line("-> {$group->name}");

            try {
                $count = $manager->importHistory($group, (int) $this->option('limit'), $since);
                $total += $count;
                $this->info("   {$count} mesaj aktarildi");
            } catch (\Throwable $e) {
                $this->error('   hata: ' . $e->getMessage());
            }
        }

        $this->newLine();
        $this->info("Toplam {$total} mesaj aktarildi.");

        return self::SUCCESS;
    }

    private function resolveGroups()
    {
        if ($this->option('all')) {
            return WhatsAppGroup::where('is_active', true)->orderBy('name')->get();
        }

        $needles = (array) $this->option('group');

        if (empty($needles)) {
            return collect();
        }

        return WhatsAppGroup::where(function ($query) use ($needles) {
            foreach ($needles as $needle) {
                $query->orWhere('external_id', $needle)
                    ->orWhere('name', 'like', '%' . $needle . '%');
            }
        })->orderBy('name')->get();
    }
}
