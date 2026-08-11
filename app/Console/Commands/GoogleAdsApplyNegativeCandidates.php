<?php

namespace App\Console\Commands;

use App\Services\GoogleAdsRestClient;
use DateTimeImmutable;
use Illuminate\Console\Command;
use Throwable;

class GoogleAdsApplyNegativeCandidates extends Command
{
    protected $signature = 'google-ads:apply-negative-candidates
        {--days=30 : Number of completed days to analyze}
        {--limit=50 : Maximum candidates to add}
        {--min-cost=0.5 : Minimum wasted cost per search term}
        {--apply : Add candidates to the shared negative keyword list}';

    protected $description = 'Add high-confidence negative search terms to the shared Google Ads negative keyword list';

    private const SHARED_NEGATIVE_SET_NAME = 'Paris Via - Shared Negative Keywords';

    private array $negativePatterns = [
        'self-drive/rental without chauffeur' => [
            'sans chauffeur',
            'self drive',
            'self-drive',
            'rent a car',
            'rental car',
            'car rental',
            'location voiture',
            'location de voiture',
            'voiture de location',
            'louer une voiture',
            'rent car',
            'car rent',
            'budget',
            'drivalia',
            'avis',
            'hertz',
            'sixt',
            'europcar',
            'enterprise',
            'alamo',
        ],
        'jobs/recruitment' => [
            'emploi',
            'recrutement',
            'salaire',
            'job',
            'career',
            'hiring',
            'chauffeur emploi',
            'driver job',
        ],
        'public transport/tickets' => [
            'ratp',
            'navigo',
            'metro',
            'métro',
            'ticket',
            'tickets',
            'billet',
            'horaires',
            'ligne bus',
            'bus line',
            'big bus',
            'flixbus',
            'blablabus',
            'tgv',
            'train',
            'tren',
            'cheapest way',
        ],
        'vehicle purchase/parts' => [
            'occasion',
            'acheter',
            'achat',
            'vente',
            'vendre',
            'used',
            'second hand',
            'pieces',
            'pièces',
            'parts',
        ],
        'logistics/shipping instead of passenger transport' => [
            'logistic',
            'logistics',
            'shipping',
            'freight',
            'cargo',
            'top 10 logistics',
            'fm logistic',
            'romasco',
        ],
        'outside Paris service area' => [
            'marseille',
            'lyon airport',
            'nantes airport',
            'belgium',
            'italy',
            'spain',
            'luxembourg',
            'kiem transports lux',
        ],
        'airport information only' => [
            'airport lounges',
            'terminal 2e hall',
            'distance aeroport',
            'distance aéroport',
            'distancia do aeroporto',
            'distância do aeroporto',
        ],
    ];

    public function handle(GoogleAdsRestClient $ads): int
    {
        try {
            $apply = (bool) $this->option('apply');
            $limit = max(1, min(200, (int) $this->option('limit')));
            $minCost = max(0, (float) $this->option('min-cost'));
            $days = max(1, min(365, (int) $this->option('days')));
            $end = new DateTimeImmutable('yesterday');
            $start = $end->modify('-' . ($days - 1) . ' days');

            $candidates = $this->candidates($ads, $start, $end, $minCost);
            $toAdd = array_slice($candidates, 0, $limit);

            $this->line($apply ? 'Mode: APPLY' : 'Mode: DRY RUN');
            $this->line(sprintf('Range: %s / %s', $start->format('Y-m-d'), $end->format('Y-m-d')));
            $this->line('Candidates found: ' . count($candidates));
            $this->line('Run limit: ' . $limit);

            $this->newLine();
            $this->table(['Term', 'Reason', 'Campaign', 'Cost', 'Clicks'], array_map(fn (array $row): array => [
                $row['term'],
                $row['reason'],
                $row['campaign_name'],
                number_format($row['cost'], 2),
                $row['clicks'],
            ], array_slice($toAdd, 0, 30)));

            if (! $apply) {
                $this->info('Dry run only. Re-run with --apply to add these exact negatives to the shared list.');

                return self::SUCCESS;
            }

            if ($toAdd === []) {
                $this->info('No safe negative candidates to add.');

                return self::SUCCESS;
            }

            $sharedSet = $this->findOrCreateSharedNegativeSet($ads);
            $attached = $this->attachCampaigns($ads, $sharedSet['resource_name'], $toAdd);
            $created = 0;
            $skipped = 0;
            $removedDuplicates = 0;

            foreach ($toAdd as $candidate) {
                if ($this->sharedNegativeKeywordExists($ads, $sharedSet['resource_name'], $candidate['term'], 'EXACT')) {
                    $skipped++;
                    continue;
                }

                $ads->mutate('sharedCriteria', [[
                    'create' => [
                        'sharedSet' => $sharedSet['resource_name'],
                        'keyword' => [
                            'text' => $candidate['term'],
                            'matchType' => 'EXACT',
                        ],
                    ],
                ]], [
                    'partialFailure' => false,
                    'responseContentType' => 'RESOURCE_NAME_ONLY',
                ]);

                $created++;

                if ($this->removeCampaignNegativeIfExists($ads, $candidate['campaign_id'], $candidate['term'], 'EXACT')) {
                    $removedDuplicates++;
                }
            }

            $this->info(sprintf(
                'Shared list updated. Added: %d, skipped existing: %d, attached campaigns: %d, removed campaign duplicates: %d.',
                $created,
                $skipped,
                $attached,
                $removedDuplicates
            ));

            return self::SUCCESS;
        } catch (Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }
    }

    private function candidates(GoogleAdsRestClient $ads, DateTimeImmutable $start, DateTimeImmutable $end, float $minCost): array
    {
        $rows = $ads->searchStream(sprintf(<<<'GAQL'
SELECT
  campaign.id,
  campaign.resource_name,
  campaign.name,
  campaign.status,
  campaign.advertising_channel_type,
  ad_group.name,
  search_term_view.search_term,
  metrics.impressions,
  metrics.clicks,
  metrics.cost_micros,
  metrics.conversions
FROM search_term_view
WHERE segments.date BETWEEN '%s' AND '%s'
  AND campaign.status = ENABLED
  AND campaign.advertising_channel_type = SEARCH
ORDER BY metrics.cost_micros DESC
LIMIT 500
GAQL, $start->format('Y-m-d'), $end->format('Y-m-d')));

        $candidates = [];
        foreach ($rows as $row) {
            $term = trim((string) data_get($row, 'searchTermView.searchTerm'));
            $cost = ((int) data_get($row, 'metrics.costMicros', 0)) / 1000000;
            $conversions = (float) data_get($row, 'metrics.conversions', 0);
            $reason = $this->negativeReason($term);

            if (
                $term === ''
                || strlen($term) > 80
                || count(explode(' ', $this->normalize($term))) > 10
                || ! $reason
                || $cost < $minCost
                || $conversions > 0
            ) {
                continue;
            }

            $candidates[] = [
                'campaign_id' => (string) data_get($row, 'campaign.id'),
                'campaign_name' => (string) data_get($row, 'campaign.name'),
                'campaign_resource_name' => (string) data_get($row, 'campaign.resourceName'),
                'ad_group' => (string) data_get($row, 'adGroup.name'),
                'term' => $term,
                'reason' => $reason,
                'cost' => round($cost, 2),
                'clicks' => (int) data_get($row, 'metrics.clicks', 0),
            ];
        }

        $deduped = [];
        foreach ($candidates as $candidate) {
            $key = $this->normalize($candidate['term']) . '|' . $candidate['campaign_id'];
            $deduped[$key] ??= $candidate;
        }

        return array_values($deduped);
    }

    private function negativeReason(string $term): ?string
    {
        $normalized = $this->normalize($term);
        $explicitDriverService = str_contains($normalized, 'chauffeur')
            || str_contains($normalized, 'with driver')
            || str_contains($normalized, 'avec chauffeur');

        foreach ($this->negativePatterns as $reason => $patterns) {
            foreach ($patterns as $pattern) {
                if (! str_contains($normalized, $this->normalize($pattern))) {
                    continue;
                }

                if ($reason === 'self-drive/rental without chauffeur' && $explicitDriverService) {
                    continue;
                }

                return $reason;
            }
        }

        return null;
    }

    private function findOrCreateSharedNegativeSet(GoogleAdsRestClient $ads): array
    {
        $sharedSet = $this->findSharedNegativeSet($ads);
        if ($sharedSet) {
            return $sharedSet;
        }

        $response = $ads->mutate('sharedSets', [[
            'create' => [
                'name' => self::SHARED_NEGATIVE_SET_NAME,
                'type' => 'NEGATIVE_KEYWORDS',
            ],
        ]], [
            'responseContentType' => 'RESOURCE_NAME_ONLY',
        ]);

        $resourceName = (string) data_get($response, 'results.0.resourceName');
        if ($resourceName === '') {
            throw new \RuntimeException('Shared negative keyword list could not be created.');
        }

        return [
            'resource_name' => $resourceName,
            'name' => self::SHARED_NEGATIVE_SET_NAME,
        ];
    }

    private function findSharedNegativeSet(GoogleAdsRestClient $ads): ?array
    {
        $escapedName = $this->gaqlString(self::SHARED_NEGATIVE_SET_NAME);
        $rows = $ads->searchStream(sprintf(<<<GAQL
SELECT
  shared_set.resource_name,
  shared_set.name,
  shared_set.type
FROM shared_set
WHERE shared_set.name = '%s'
  AND shared_set.type = NEGATIVE_KEYWORDS
LIMIT 1
GAQL, $escapedName));

        $resourceName = (string) data_get($rows, '0.sharedSet.resourceName');
        if ($resourceName === '') {
            return null;
        }

        return [
            'resource_name' => $resourceName,
            'name' => (string) data_get($rows, '0.sharedSet.name'),
        ];
    }

    private function attachCampaigns(GoogleAdsRestClient $ads, string $sharedSetResourceName, array $candidates): int
    {
        $attached = 0;
        $campaigns = [];

        foreach ($candidates as $candidate) {
            if ($candidate['campaign_id'] === '' || $candidate['campaign_resource_name'] === '') {
                continue;
            }

            $campaigns[$candidate['campaign_id']] = [
                'id' => $candidate['campaign_id'],
                'resource_name' => $candidate['campaign_resource_name'],
            ];
        }

        foreach ($campaigns as $campaign) {
            if ($this->campaignSharedSetExists($ads, $sharedSetResourceName, $campaign['id'])) {
                continue;
            }

            $ads->mutate('campaignSharedSets', [[
                'create' => [
                    'campaign' => $campaign['resource_name'],
                    'sharedSet' => $sharedSetResourceName,
                ],
            ]], [
                'responseContentType' => 'RESOURCE_NAME_ONLY',
            ]);

            $attached++;
        }

        return $attached;
    }

    private function campaignSharedSetExists(GoogleAdsRestClient $ads, string $sharedSetResourceName, string $campaignId): bool
    {
        $campaignId = preg_replace('/\D+/', '', $campaignId) ?: '';
        $rows = $ads->searchStream(sprintf(<<<GAQL
SELECT
  campaign_shared_set.resource_name
FROM campaign_shared_set
WHERE campaign.id = %s
  AND campaign_shared_set.shared_set = '%s'
  AND campaign_shared_set.status != REMOVED
LIMIT 1
GAQL, $campaignId, $sharedSetResourceName));

        return $rows !== [];
    }

    private function sharedNegativeKeywordExists(GoogleAdsRestClient $ads, string $sharedSetResourceName, string $term, string $matchType): bool
    {
        $escapedTerm = $this->gaqlString($term);
        $rows = $ads->searchStream(sprintf(<<<GAQL
SELECT
  shared_criterion.keyword.text,
  shared_criterion.keyword.match_type
FROM shared_criterion
WHERE shared_criterion.shared_set = '%s'
  AND shared_criterion.type = KEYWORD
  AND shared_criterion.keyword.text = '%s'
  AND shared_criterion.keyword.match_type = %s
LIMIT 1
GAQL, $sharedSetResourceName, $escapedTerm, $matchType));

        return $rows !== [];
    }

    private function removeCampaignNegativeIfExists(GoogleAdsRestClient $ads, string $campaignId, string $term, string $matchType): bool
    {
        $campaignId = preg_replace('/\D+/', '', $campaignId) ?: '';
        $escapedTerm = $this->gaqlString($term);
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

    private function gaqlString(string $value): string
    {
        return str_replace(['\\', "'"], ['\\\\', "\\'"], $value);
    }

    private function normalize(string $value): string
    {
        $value = strtolower($value);
        $ascii = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
        $value = $ascii !== false ? $ascii : $value;
        $value = preg_replace('/[^a-z0-9]+/', ' ', $value) ?: '';

        return trim($value);
    }
}
