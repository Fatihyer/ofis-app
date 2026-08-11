<?php

namespace App\Console\Commands;

use App\Services\GoogleAdsRestClient;
use Illuminate\Console\Command;
use Throwable;

class GoogleAdsCleanNegativeKeywords extends Command
{
    protected $signature = 'google-ads:clean-negative-keywords
        {--apply : Delete redundant campaign-level negatives from Google Ads}
        {--limit=250 : Maximum negatives to delete in one run}
        {--include-paused : Include paused Search campaigns}';

    protected $description = 'Find and remove campaign-level negative keywords already covered by broader negatives or shared lists';

    public function handle(GoogleAdsRestClient $ads): int
    {
        try {
            $apply = (bool) $this->option('apply');
            $limit = max(1, min(1000, (int) $this->option('limit')));

            $campaignNegatives = $this->campaignNegatives($ads);
            $sharedNegativesByCampaign = $this->sharedNegativesByCampaign($ads);

            $redundant = $this->findRedundantCampaignNegatives($campaignNegatives, $sharedNegativesByCampaign);
            $toRemove = array_slice($redundant, 0, $limit);

            $this->line($apply ? 'Mode: APPLY' : 'Mode: DRY RUN');
            $this->line('Campaign negatives scanned: ' . count($campaignNegatives));
            $this->line('Redundant campaign negatives found: ' . count($redundant));
            $this->line('Run limit: ' . $limit);

            $this->newLine();
            $this->table(
                ['Campaign', 'Remove', 'Match', 'Covered by', 'Scope', 'Reason'],
                array_map(fn (array $row): array => [
                    $row['campaign_name'],
                    $row['text'],
                    $row['match_type'],
                    $row['covered_by']['text'] . ' (' . $row['covered_by']['match_type'] . ')',
                    $row['covered_by']['scope'],
                    $row['reason'],
                ], array_slice($toRemove, 0, 40))
            );

            if (! $apply) {
                $this->info('Dry run only. Re-run with --apply to delete these redundant campaign negatives.');

                return self::SUCCESS;
            }

            if ($toRemove === []) {
                $this->info('Nothing to remove.');

                return self::SUCCESS;
            }

            $operations = array_map(fn (array $row): array => [
                'remove' => $row['resource_name'],
            ], $toRemove);

            $response = $ads->mutate('campaignCriteria', $operations, [
                'partialFailure' => true,
                'responseContentType' => 'RESOURCE_NAME_ONLY',
            ]);

            if (! empty($response['partialFailureError'])) {
                $this->warn('Partial failure: ' . json_encode($response['partialFailureError'], JSON_UNESCAPED_UNICODE));
            }

            $this->info('Removed redundant campaign negatives: ' . count($toRemove));

            return self::SUCCESS;
        } catch (Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }
    }

    private function campaignNegatives(GoogleAdsRestClient $ads): array
    {
        $statusClause = $this->option('include-paused')
            ? 'campaign.status IN (ENABLED, PAUSED)'
            : 'campaign.status = ENABLED';

        $rows = $ads->searchStream(sprintf(<<<GAQL
SELECT
  campaign.id,
  campaign.name,
  campaign.status,
  campaign_criterion.resource_name,
  campaign_criterion.keyword.text,
  campaign_criterion.keyword.match_type
FROM campaign_criterion
WHERE %s
  AND campaign.advertising_channel_type = SEARCH
  AND campaign_criterion.type = KEYWORD
  AND campaign_criterion.negative = TRUE
ORDER BY campaign.id
LIMIT 10000
GAQL, $statusClause));

        return array_values(array_filter(array_map(fn (array $row): array => [
            'scope' => 'campaign',
            'campaign_id' => (string) data_get($row, 'campaign.id'),
            'campaign_name' => (string) data_get($row, 'campaign.name'),
            'resource_name' => (string) data_get($row, 'campaignCriterion.resourceName'),
            'text' => (string) data_get($row, 'campaignCriterion.keyword.text'),
            'match_type' => (string) data_get($row, 'campaignCriterion.keyword.matchType'),
        ], $rows), fn (array $row): bool => $row['resource_name'] !== '' && $row['text'] !== ''));
    }

    private function sharedNegativesByCampaign(GoogleAdsRestClient $ads): array
    {
        $attached = $ads->searchStream(<<<'GAQL'
SELECT
  campaign.id,
  campaign.name,
  campaign_shared_set.shared_set,
  campaign_shared_set.status
FROM campaign_shared_set
WHERE campaign_shared_set.status != REMOVED
  AND campaign.status IN (ENABLED, PAUSED)
GAQL);

        $sharedSetIdsByCampaign = [];
        foreach ($attached as $row) {
            $campaignId = (string) data_get($row, 'campaign.id');
            $sharedSet = (string) data_get($row, 'campaignSharedSet.sharedSet');
            if ($campaignId !== '' && $sharedSet !== '') {
                $sharedSetIdsByCampaign[$campaignId][] = $sharedSet;
            }
        }

        $criteriaBySharedSet = [];
        foreach (array_unique(array_merge(...array_values($sharedSetIdsByCampaign ?: [[]]))) as $sharedSet) {
            $criteriaBySharedSet[$sharedSet] = $this->sharedCriteria($ads, $sharedSet);
        }

        $result = [];
        foreach ($sharedSetIdsByCampaign as $campaignId => $sharedSets) {
            $result[$campaignId] = [];
            foreach ($sharedSets as $sharedSet) {
                foreach ($criteriaBySharedSet[$sharedSet] ?? [] as $criterion) {
                    $result[$campaignId][] = $criterion;
                }
            }
        }

        return $result;
    }

    private function sharedCriteria(GoogleAdsRestClient $ads, string $sharedSet): array
    {
        $rows = $ads->searchStream(sprintf(<<<GAQL
SELECT
  shared_criterion.resource_name,
  shared_criterion.keyword.text,
  shared_criterion.keyword.match_type
FROM shared_criterion
WHERE shared_criterion.shared_set = '%s'
  AND shared_criterion.type = KEYWORD
LIMIT 10000
GAQL, $sharedSet));

        return array_values(array_filter(array_map(fn (array $row): array => [
            'scope' => 'shared',
            'resource_name' => (string) data_get($row, 'sharedCriterion.resourceName'),
            'text' => (string) data_get($row, 'sharedCriterion.keyword.text'),
            'match_type' => (string) data_get($row, 'sharedCriterion.keyword.matchType'),
        ], $rows), fn (array $row): bool => $row['text'] !== ''));
    }

    private function findRedundantCampaignNegatives(array $campaignNegatives, array $sharedNegativesByCampaign): array
    {
        $byCampaign = [];
        foreach ($campaignNegatives as $negative) {
            $byCampaign[$negative['campaign_id']][] = $negative;
        }

        $redundant = [];
        foreach ($campaignNegatives as $candidate) {
            $blockers = array_merge(
                $byCampaign[$candidate['campaign_id']] ?? [],
                $sharedNegativesByCampaign[$candidate['campaign_id']] ?? []
            );

            foreach ($blockers as $blocker) {
                if (($blocker['resource_name'] ?? null) === $candidate['resource_name']) {
                    continue;
                }

                $reason = $this->coverageReason($candidate, $blocker);
                if (! $reason) {
                    continue;
                }

                $redundant[] = $candidate + [
                    'covered_by' => $blocker,
                    'reason' => $reason,
                ];
                break;
            }
        }

        usort($redundant, fn (array $a, array $b): int => [
            $a['campaign_name'],
            $a['text'],
        ] <=> [
            $b['campaign_name'],
            $b['text'],
        ]);

        return $redundant;
    }

    private function coverageReason(array $candidate, array $blocker): ?string
    {
        $candidateTerms = $this->terms($candidate['text']);
        $blockerTerms = $this->terms($blocker['text']);

        if ($candidateTerms === [] || $blockerTerms === []) {
            return null;
        }

        $candidateText = implode(' ', $candidateTerms);
        $blockerText = implode(' ', $blockerTerms);

        if ($blocker['match_type'] === 'EXACT') {
            if ($candidateText !== $blockerText) {
                return null;
            }

            if (($blocker['scope'] ?? '') === 'shared') {
                return 'same exact negative in shared list';
            }

            return strcmp((string) $blocker['resource_name'], (string) $candidate['resource_name']) < 0
                ? 'same normalized exact negative kept once'
                : null;
        }

        if ($blocker['match_type'] === 'PHRASE') {
            if ($candidate['match_type'] === 'PHRASE' && $candidateText === $blockerText && ($blocker['scope'] ?? '') !== 'shared') {
                return strcmp((string) $blocker['resource_name'], (string) $candidate['resource_name']) < 0
                    ? 'same normalized phrase negative kept once'
                    : null;
            }

            return $this->containsPhrase($candidateTerms, $blockerTerms)
                ? 'covered by negative phrase'
                : null;
        }

        if ($blocker['match_type'] === 'BROAD') {
            if ($candidate['match_type'] === 'BROAD' && $candidateText === $blockerText && ($blocker['scope'] ?? '') !== 'shared') {
                return strcmp((string) $blocker['resource_name'], (string) $candidate['resource_name']) < 0
                    ? 'same normalized broad negative kept once'
                    : null;
            }

            return count(array_diff(array_unique($blockerTerms), array_unique($candidateTerms))) === 0
                ? 'covered by negative broad'
                : null;
        }

        return null;
    }

    private function containsPhrase(array $candidateTerms, array $blockerTerms): bool
    {
        $length = count($blockerTerms);
        if ($length > count($candidateTerms)) {
            return false;
        }

        for ($i = 0; $i <= count($candidateTerms) - $length; $i++) {
            if (array_slice($candidateTerms, $i, $length) === $blockerTerms) {
                return true;
            }
        }

        return false;
    }

    private function terms(string $value): array
    {
        $value = strtolower($value);
        $ascii = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
        $value = $ascii !== false ? $ascii : $value;
        $value = preg_replace('/[^a-z0-9]+/', ' ', $value) ?: '';

        return array_values(array_filter(explode(' ', trim($value))));
    }
}
