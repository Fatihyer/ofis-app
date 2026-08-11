<?php

namespace App\Console\Commands;

use App\Services\GoogleAdsRestClient;
use Illuminate\Console\Command;
use Throwable;

class GoogleAdsTestConnection extends Command
{
    protected $signature = 'google-ads:test
        {--customer= : Google Ads customer ID. Defaults to GOOGLE_ADS_CUSTOMER_ID}
        {--no-campaigns : Only test OAuth and accessible customers}';

    protected $description = 'Test the Google Ads API connection and read basic account data';

    public function handle(GoogleAdsRestClient $ads): int
    {
        $customerId = $ads->customerId((string) ($this->option('customer') ?: ''));

        $this->line('Google Ads API version: ' . $ads->apiVersion());
        $this->line('Customer ID: ' . ($customerId !== '' ? $customerId : 'not set'));
        $this->line('Login Customer ID: ' . ($ads->loginCustomerId() !== '' ? $ads->loginCustomerId() : 'not set'));
        $this->line('Developer token: ' . $ads->developerTokenLength() . ' chars');

        try {
            $customers = $ads->listAccessibleCustomers();

            $this->newLine();
            $this->info('Accessible customers');
            if ($customers === []) {
                $this->warn('No accessible customers returned for this OAuth user.');
            } else {
                foreach ($customers as $resourceName) {
                    $this->line('- ' . $resourceName);
                }
            }

            if ($this->option('no-campaigns')) {
                return self::SUCCESS;
            }

            $this->printCustomer($ads, $customerId);
            $this->printCampaigns($ads, $customerId);
            $this->printConversionActions($ads, $customerId);

            return self::SUCCESS;
        } catch (Throwable $e) {
            $this->newLine();
            $this->error($e->getMessage());

            return self::FAILURE;
        }
    }

    private function printCustomer(GoogleAdsRestClient $ads, string $customerId): void
    {
        $rows = $ads->searchStream(<<<'GAQL'
SELECT
  customer.id,
  customer.descriptive_name,
  customer.currency_code,
  customer.time_zone
FROM customer
LIMIT 1
GAQL, $customerId);

        $this->newLine();
        $this->info('Account');
        $this->table(
            ['ID', 'Name', 'Currency', 'Time zone'],
            array_map(fn (array $row): array => [
                data_get($row, 'customer.id'),
                data_get($row, 'customer.descriptiveName'),
                data_get($row, 'customer.currencyCode'),
                data_get($row, 'customer.timeZone'),
            ], $rows)
        );
    }

    private function printCampaigns(GoogleAdsRestClient $ads, string $customerId): void
    {
        $rows = $ads->searchStream(<<<'GAQL'
SELECT
  campaign.id,
  campaign.name,
  campaign.status,
  metrics.impressions,
  metrics.clicks,
  metrics.cost_micros
FROM campaign
WHERE segments.date DURING LAST_30_DAYS
ORDER BY metrics.impressions DESC
LIMIT 10
GAQL, $customerId);

        $this->newLine();
        $this->info('Campaigns, last 30 days');
        $this->table(
            ['ID', 'Name', 'Status', 'Impr.', 'Clicks', 'Cost'],
            array_map(fn (array $row): array => [
                data_get($row, 'campaign.id'),
                data_get($row, 'campaign.name'),
                data_get($row, 'campaign.status'),
                data_get($row, 'metrics.impressions', 0),
                data_get($row, 'metrics.clicks', 0),
                number_format(((int) data_get($row, 'metrics.costMicros', 0)) / 1000000, 2),
            ], $rows)
        );
    }

    private function printConversionActions(GoogleAdsRestClient $ads, string $customerId): void
    {
        $rows = $ads->searchStream(<<<'GAQL'
SELECT
  conversion_action.id,
  conversion_action.name,
  conversion_action.type,
  conversion_action.status
FROM conversion_action
ORDER BY conversion_action.id DESC
LIMIT 20
GAQL, $customerId);

        $this->newLine();
        $this->info('Conversion actions');
        $this->table(
            ['ID', 'Name', 'Type', 'Status'],
            array_map(fn (array $row): array => [
                data_get($row, 'conversionAction.id'),
                data_get($row, 'conversionAction.name'),
                data_get($row, 'conversionAction.type'),
                data_get($row, 'conversionAction.status'),
            ], $rows)
        );
    }
}
