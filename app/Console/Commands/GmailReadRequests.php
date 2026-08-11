<?php

namespace App\Console\Commands;

use App\Http\Controllers\GmailController;
use Illuminate\Console\Command;

class GmailReadRequests extends Command
{
    protected $signature = 'gmail:read-requests
        {--accounts=contact,resparis : Virgülle ayrılmış IMAP hesapları}
        {--days=7 : Gmail içinde geriye doğru taranacak gün sayısı}
        {--limit=100 : Her hesap için en fazla mail sayısı}';

    protected $description = 'Gmail gelen kutularını okuyup mailleri demande senkron tablosuna alır.';

    public function handle(GmailController $gmail): int
    {
        $allowedAccounts = ['contact', 'resparis', 'contactfrance', 'resfrance', 'paris', 'sales', 'sales2'];
        $accounts = collect(explode(',', (string) $this->option('accounts')))
            ->map(fn ($account) => trim($account))
            ->filter()
            ->unique()
            ->values();

        $days = max(1, min(90, (int) $this->option('days')));
        $limit = max(10, min(500, (int) $this->option('limit')));
        $failed = false;

        foreach ($accounts as $account) {
            if (! in_array($account, $allowedAccounts, true)) {
                $this->warn("Hesap atlandı: {$account}");
                continue;
            }

            try {
                $count = $gmail->syncMailboxForScheduler($account, $days, $limit);
                $this->info("{$account}: {$count} mail kontrol edildi.");
            } catch (\Throwable $e) {
                $failed = true;
                $this->error("{$account}: " . $e->getMessage());
                report($e);
            }
        }

        return $failed ? self::FAILURE : self::SUCCESS;
    }
}
