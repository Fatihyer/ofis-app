<?php

namespace App\Console\Commands;

use App\Services\GoogleAdsRestClient;
use Illuminate\Console\Command;
use Throwable;

class GoogleAdsSetupSharedNegativeList extends Command
{
    protected $signature = 'google-ads:setup-shared-negative-list
        {--seed-core : Add the core Paris Via negative terms to the shared list}
        {--remove-campaign-duplicates : Remove campaign-level duplicates after the shared list is attached}';

    protected $description = 'Create and attach the Paris Via shared negative keyword list';

    private const SHARED_SET_NAME = 'Paris Via - Shared Negative Keywords';

    private array $coreNegatives = [
        ['transport company of italy', 'EXACT'],
        ['budget car rental france contact', 'EXACT'],
        ['romasco shipping bvi', 'EXACT'],
        ['transport company spain', 'EXACT'],
        ['transport company in spain', 'EXACT'],
        ['kiem transports lux', 'EXACT'],
        ['big bus paris phone number', 'EXACT'],
        ['fm logistic france', 'EXACT'],
        ['cdg airport lounges', 'EXACT'],
        ['transport company belgium', 'EXACT'],
        ['luxury car rental paris', 'EXACT'],
        ['cdg terminal 2e hall m', 'EXACT'],
        ['tren de paris a disneyland', 'EXACT'],
        ['tgv france', 'EXACT'],
        ['paris beauvais shuttle bus tickets', 'EXACT'],
        ['rent car paris france', 'EXACT'],
        ['blablabus france', 'EXACT'],
        ['marseille vtc', 'EXACT'],
        ['transfers from nantes airport', 'EXACT'],
        ['car rent in france', 'EXACT'],
        ['distance aéroport charles de gaulle et tour eiffel', 'EXACT'],
        ['lyon airport to city centre cheapest', 'EXACT'],
        ['cheapest way', 'PHRASE'],
        ['top 10 logistics companies in france', 'EXACT'],
        ['french rental car', 'EXACT'],
    ];

    public function handle(GoogleAdsRestClient $ads): int
    {
        try {
            $sharedSet = $this->findOrCreateSharedSet($ads);
            $campaigns = $this->enabledSearchCampaigns($ads);
            $attached = 0;

            foreach ($campaigns as $campaign) {
                if ($this->attach($ads, $sharedSet['resource_name'], $campaign)) {
                    $attached++;
                }
            }

            $seeded = 0;
            $removedDuplicates = 0;

            if ($this->option('seed-core')) {
                foreach ($this->coreNegatives as [$term, $matchType]) {
                    if ($this->addSharedNegativeIfMissing($ads, $sharedSet['resource_name'], $term, $matchType)) {
                        $seeded++;
                    }

                    if ($this->option('remove-campaign-duplicates')) {
                        foreach ($campaigns as $campaign) {
                            if ($this->removeCampaignDuplicate($ads, $campaign['id'], $term, $matchType)) {
                                $removedDuplicates++;
                            }
                        }
                    }
                }
            }

            $this->info('Shared negative list ready.');
            $this->line('List: ' . $sharedSet['name']);
            $this->line('Resource: ' . $sharedSet['resource_name']);
            $this->line('Search campaigns: ' . count($campaigns));
            $this->line('New attachments: ' . $attached);
            $this->line('Seeded shared negatives: ' . $seeded);
            $this->line('Removed campaign duplicates: ' . $removedDuplicates);
            $this->line('Shared negative count: ' . $this->sharedNegativeCount($ads, $sharedSet['resource_name']));

            foreach ($campaigns as $campaign) {
                $this->line(sprintf(
                    'Campaign negatives: %s = %d',
                    $campaign['name'],
                    $this->campaignNegativeCount($ads, $campaign['id'])
                ));
            }

            return self::SUCCESS;
        } catch (Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }
    }

    private function findOrCreateSharedSet(GoogleAdsRestClient $ads): array
    {
        $sharedSet = $this->findSharedSet($ads);
        if ($sharedSet) {
            return $sharedSet;
        }

        $response = $ads->mutate('sharedSets', [[
            'create' => [
                'name' => self::SHARED_SET_NAME,
                'type' => 'NEGATIVE_KEYWORDS',
            ],
        ]], [
            'responseContentType' => 'RESOURCE_NAME_ONLY',
        ]);

        $resourceName = (string) data_get($response, 'results.0.resourceName');
        if ($resourceName === '') {
            throw new \RuntimeException('Shared negative list could not be created.');
        }

        return [
            'name' => self::SHARED_SET_NAME,
            'resource_name' => $resourceName,
        ];
    }

    private function findSharedSet(GoogleAdsRestClient $ads): ?array
    {
        $name = str_replace("'", "\\'", self::SHARED_SET_NAME);
        $rows = $ads->searchStream(sprintf(<<<GAQL
SELECT
  shared_set.resource_name,
  shared_set.name,
  shared_set.type
FROM shared_set
WHERE shared_set.name = '%s'
  AND shared_set.type = NEGATIVE_KEYWORDS
LIMIT 1
GAQL, $name));

        $row = $rows[0] ?? null;
        if (! $row) {
            return null;
        }

        return [
            'name' => data_get($row, 'sharedSet.name'),
            'resource_name' => data_get($row, 'sharedSet.resourceName'),
        ];
    }

    private function enabledSearchCampaigns(GoogleAdsRestClient $ads): array
    {
        $rows = $ads->searchStream(<<<'GAQL'
SELECT
  campaign.id,
  campaign.resource_name,
  campaign.name
FROM campaign
WHERE campaign.status = ENABLED
  AND campaign.advertising_channel_type = SEARCH
ORDER BY campaign.id
GAQL);

        return array_map(fn (array $row): array => [
            'id' => (string) data_get($row, 'campaign.id'),
            'name' => data_get($row, 'campaign.name'),
            'resource_name' => data_get($row, 'campaign.resourceName'),
        ], $rows);
    }

    private function attach(GoogleAdsRestClient $ads, string $sharedSetResourceName, array $campaign): bool
    {
        $rows = $ads->searchStream(sprintf(<<<GAQL
SELECT
  campaign_shared_set.resource_name
FROM campaign_shared_set
WHERE campaign.id = %s
  AND campaign_shared_set.shared_set = '%s'
  AND campaign_shared_set.status != REMOVED
LIMIT 1
GAQL, $campaign['id'], $sharedSetResourceName));

        if ($rows !== []) {
            return false;
        }

        $ads->mutate('campaignSharedSets', [[
            'create' => [
                'campaign' => $campaign['resource_name'],
                'sharedSet' => $sharedSetResourceName,
            ],
        ]], [
            'responseContentType' => 'RESOURCE_NAME_ONLY',
        ]);

        return true;
    }

    private function addSharedNegativeIfMissing(GoogleAdsRestClient $ads, string $sharedSetResourceName, string $term, string $matchType): bool
    {
        $escapedTerm = str_replace("'", "\\'", $term);
        $rows = $ads->searchStream(sprintf(<<<GAQL
SELECT
  shared_criterion.keyword.text
FROM shared_criterion
WHERE shared_criterion.shared_set = '%s'
  AND shared_criterion.type = KEYWORD
  AND shared_criterion.keyword.text = '%s'
  AND shared_criterion.keyword.match_type = %s
LIMIT 1
GAQL, $sharedSetResourceName, $escapedTerm, $matchType));

        if ($rows !== []) {
            return false;
        }

        $ads->mutate('sharedCriteria', [[
            'create' => [
                'sharedSet' => $sharedSetResourceName,
                'keyword' => [
                    'text' => $term,
                    'matchType' => $matchType,
                ],
            ],
        ]], [
            'responseContentType' => 'RESOURCE_NAME_ONLY',
        ]);

        return true;
    }

    private function removeCampaignDuplicate(GoogleAdsRestClient $ads, string $campaignId, string $term, string $matchType): bool
    {
        $campaignId = preg_replace('/\D+/', '', $campaignId) ?: '';
        $escapedTerm = str_replace("'", "\\'", $term);
        $rows = $ads->searchStream(sprintf(<<<GAQL
SELECT
  campaign_criterion.resource_name
FROM campaign_criterion
WHERE campaign.id = %s
  AND campaign_criterion.type = KEYWORD
  AND campaign_criterion.negative = TRUE
  AND campaign_criterion.keyword.text = '%s'
  AND campaign_criterion.keyword.match_type = %s
LIMIT 1
GAQL, $campaignId, $escapedTerm, $matchType));

        $resourceName = (string) data_get($rows, '0.campaignCriterion.resourceName');
        if ($resourceName === '') {
            return false;
        }

        $ads->mutate('campaignCriteria', [[
            'remove' => $resourceName,
        ]], [
            'responseContentType' => 'RESOURCE_NAME_ONLY',
        ]);

        return true;
    }

    private function sharedNegativeCount(GoogleAdsRestClient $ads, string $sharedSetResourceName): int
    {
        $rows = $ads->searchStream(sprintf(<<<GAQL
SELECT
  shared_criterion.criterion_id
FROM shared_criterion
WHERE shared_criterion.shared_set = '%s'
  AND shared_criterion.type = KEYWORD
LIMIT 10000
GAQL, $sharedSetResourceName));

        return count($rows);
    }

    private function campaignNegativeCount(GoogleAdsRestClient $ads, string $campaignId): int
    {
        $campaignId = preg_replace('/\D+/', '', $campaignId) ?: '';
        $rows = $ads->searchStream(sprintf(<<<GAQL
SELECT
  campaign_criterion.criterion_id
FROM campaign_criterion
WHERE campaign.id = %s
  AND campaign_criterion.type = KEYWORD
  AND campaign_criterion.negative = TRUE
LIMIT 10000
GAQL, $campaignId));

        return count($rows);
    }
}
