<?php

namespace App\Console\Commands;

use App\Models\Acente;
use App\Models\TicketSale;
use App\Models\WhatsAppGroup;
use App\Models\WhatsAppGroupMessage;
use App\Services\Tickets\TicketSaleExtractor;
use Illuminate\Console\Command;

class ExtractTicketSales extends Command
{
    protected $signature = 'tickets:extract
                            {--group=* : Grup ismi parcasi (varsayilan: bilet gruplari)}
                            {--days=0 : Sadece son N gunun mesajlari}';

    protected $description = 'WhatsApp bilet gruplarindaki mesajlardan bilet satis kayitlarini cikarir';

    /** Varsayilan olarak taranan gruplar */
    private const DEFAULT_GROUPS = ['Bateaux', 'Disney', 'Bilet'];

    public function handle(TicketSaleExtractor $extractor): int
    {
        $needles = (array) ($this->option('group') ?: self::DEFAULT_GROUPS);

        $groups = WhatsAppGroup::where(function ($query) use ($needles) {
            foreach ($needles as $needle) {
                $query->orWhere('name', 'like', '%' . $needle . '%');
            }
        })->get();

        if ($groups->isEmpty()) {
            $this->error('Eslesen grup yok.');

            return self::FAILURE;
        }

        $acenteMap = $this->acenteMap($extractor);
        $created = 0;
        $updated = 0;

        foreach ($groups as $group) {
            $query = WhatsAppGroupMessage::where('whatsapp_group_id', $group->id)
                ->whereNotNull('body')
                ->when((int) $this->option('days') > 0, fn ($q) => $q->where('sent_at', '>=', now()->subDays((int) $this->option('days'))));

            $this->line("-> {$group->name} ({$query->count()} mesaj)");

            $query->orderBy('id')->chunk(200, function ($messages) use ($extractor, $acenteMap, &$created, &$updated) {
                foreach ($messages as $message) {
                    foreach ($extractor->extract($message) as $row) {
                        $row['acente_id'] = $this->matchAcente($row['customer_key'], $acenteMap);

                        $sale = TicketSale::updateOrCreate(
                            [
                                'whatsapp_group_message_id' => $row['whatsapp_group_message_id'],
                                'source_hash' => $row['source_hash'],
                            ],
                            $row
                        );

                        $sale->wasRecentlyCreated ? $created++ : $updated++;
                    }
                }
            });
        }

        $this->newLine();
        $this->info("{$created} yeni satis kaydi, {$updated} guncelleme.");
        $this->info('Toplam: ' . TicketSale::count() . ' kayit / ' . number_format((float) TicketSale::sum('amount'), 0, ',', '.') . ' EUR');

        return self::SUCCESS;
    }

    /** @return array<string, int> normalize edilmis acente adi => id */
    private function acenteMap(TicketSaleExtractor $extractor): array
    {
        $map = [];

        foreach (Acente::query()->get(['id', 'name']) as $acente) {
            $key = $extractor->customerKey((string) $acente->name);

            if ($key !== '' && $key !== 'bilinmiyor' && !isset($map[$key])) {
                $map[$key] = $acente->id;
            }
        }

        return $map;
    }

    /** Jenerik kelimeler eslestirmede ayirt edici degildir */
    private const GENERIC = [
        'travel', 'turizm', 'tur', 'tours', 'dmc', 'international', 'paris', 'via', 'guide',
        'sl', 'ltd', 'llc', 'sarl', 'sas', 'company', 'group', 'agency', 'events', 'evenements',
    ];

    private function matchAcente(string $customerKey, array $acenteMap): ?int
    {
        if (isset($acenteMap[$customerKey])) {
            return $acenteMap[$customerKey];
        }

        $customerTokens = $this->distinctiveTokens($customerKey);

        if (empty($customerTokens)) {
            return null;
        }

        $best = null;
        $bestScore = 0;

        foreach ($acenteMap as $name => $id) {
            $shared = array_intersect($customerTokens, $this->distinctiveTokens($name));

            if (empty($shared)) {
                continue;
            }

            // skor: ortak kelime sayisi + en uzun ortak kelimenin uzunlugu
            $score = count($shared) * 100 + max(array_map('mb_strlen', $shared));

            if ($score > $bestScore) {
                $bestScore = $score;
                $best = $id;
            }
        }

        return $best;
    }

    /** @return array<int, string> */
    private function distinctiveTokens(string $value): array
    {
        $tokens = array_filter(
            explode(' ', $value),
            fn ($token) => mb_strlen($token) >= 3 && !in_array($token, self::GENERIC, true)
        );

        return array_values(array_unique($tokens));
    }

}
