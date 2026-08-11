<?php

namespace App\Console\Commands;

use App\Services\GoogleAdsRestClient;
use Illuminate\Console\Command;
use Throwable;

class GoogleAdsOptimize extends Command
{
    protected $signature = 'google-ads:optimize
        {--apply : Apply the changes. Without this option Google Ads only validates the mutations}
        {--campaign= : Campaign ID to optimize. Defaults to enabled Search campaigns}';

    protected $description = 'Clean conversion actions and add safe negative keywords for Paris Via';

    private array $secondaryConversionNames = [
        'Bize Ulaşın',
        'Sayfa görüntüleme (Sayfa yükleme parisvia.com/contact)',
        'Nous contacter',
        'Hakkımızda',
    ];

    private array $negativeKeywords = [
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
    ];

    public function handle(GoogleAdsRestClient $ads): int
    {
        $apply = (bool) $this->option('apply');
        $this->line($apply ? 'Mode: APPLY' : 'Mode: VALIDATE ONLY');

        try {
            $conversionOperations = $this->conversionOperations($ads);
            $campaigns = $this->targetCampaigns($ads);
            $negativeOperations = $this->negativeKeywordOperations($ads, $campaigns);

            $this->newLine();
            $this->info('Planned conversion changes');
            $this->line(count($conversionOperations) . ' conversion action(s) will be set to Secondary.');

            $this->info('Planned negative keyword changes');
            foreach ($campaigns as $campaign) {
                $count = count(array_filter(
                    $negativeOperations,
                    fn (array $operation): bool => data_get($operation, 'create.campaign') === $campaign['resource_name']
                ));
                $this->line(sprintf('- %s: %d new negative keyword(s)', $campaign['name'], $count));
            }

            $this->mutateIfNeeded($ads, 'conversionActions', $conversionOperations, $apply, false);
            $this->mutateIfNeeded($ads, 'campaignCriteria', $negativeOperations, $apply, true);

            $this->newLine();
            $this->info($apply ? 'Optimization applied.' : 'Validation passed. Re-run with --apply to apply changes.');

            return self::SUCCESS;
        } catch (Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }
    }

    private function conversionOperations(GoogleAdsRestClient $ads): array
    {
        $rows = $ads->searchStream(<<<'GAQL'
SELECT
  conversion_action.resource_name,
  conversion_action.id,
  conversion_action.name,
  conversion_action.status,
  conversion_action.type,
  conversion_action.primary_for_goal
FROM conversion_action
WHERE conversion_action.status != REMOVED
GAQL);

        $operations = [];
        foreach ($rows as $row) {
            $name = (string) data_get($row, 'conversionAction.name');
            $primaryForGoal = (bool) data_get($row, 'conversionAction.primaryForGoal', false);
            $type = (string) data_get($row, 'conversionAction.type');

            if (! in_array($name, $this->secondaryConversionNames, true) || ! $primaryForGoal) {
                continue;
            }

            if ($type === 'WEBPAGE_CODELESS') {
                $this->warn(sprintf(
                    'Manual UI needed, API does not allow mutating WEBPAGE_CODELESS conversion: %s',
                    $name
                ));
                continue;
            }

            $operations[] = [
                'update' => [
                    'resourceName' => data_get($row, 'conversionAction.resourceName'),
                    'primaryForGoal' => false,
                ],
                'updateMask' => 'primary_for_goal',
            ];

            $this->line(sprintf(
                'Secondary conversion: %s (%s)',
                $name,
                $type
            ));
        }

        return $operations;
    }

    private function targetCampaigns(GoogleAdsRestClient $ads): array
    {
        $campaignOption = preg_replace('/\D+/', '', (string) ($this->option('campaign') ?: ''));
        $where = $campaignOption !== ''
            ? 'campaign.id = ' . $campaignOption
            : "campaign.status = ENABLED AND campaign.advertising_channel_type = SEARCH";

        $rows = $ads->searchStream(sprintf(<<<GAQL
SELECT
  campaign.resource_name,
  campaign.id,
  campaign.name,
  campaign.status,
  campaign.advertising_channel_type
FROM campaign
WHERE %s
ORDER BY campaign.id
GAQL, $where));

        $campaigns = [];
        foreach ($rows as $row) {
            $campaigns[] = [
                'resource_name' => data_get($row, 'campaign.resourceName'),
                'id' => (string) data_get($row, 'campaign.id'),
                'name' => data_get($row, 'campaign.name'),
            ];
        }

        if ($campaigns === []) {
            throw new \RuntimeException('No target Search campaign found.');
        }

        return $campaigns;
    }

    private function negativeKeywordOperations(GoogleAdsRestClient $ads, array $campaigns): array
    {
        $operations = [];

        foreach ($campaigns as $campaign) {
            $existing = $this->existingNegativeKeywords($ads, $campaign['id']);
            $remainingSlots = max(0, 10000 - count($existing));
            $plannedForCampaign = 0;

            if ($remainingSlots < count($this->negativeKeywords)) {
                $this->warn(sprintf(
                    '%s has %d campaign negative keyword(s); only %d slot(s) remain.',
                    $campaign['name'],
                    count($existing),
                    $remainingSlots
                ));
            }

            foreach ($this->negativeKeywords as [$text, $matchType]) {
                if ($plannedForCampaign >= $remainingSlots) {
                    break;
                }

                $key = $this->negativeKey($text, $matchType);
                if (isset($existing[$key])) {
                    continue;
                }

                $operations[] = [
                    'create' => [
                        'campaign' => $campaign['resource_name'],
                        'negative' => true,
                        'keyword' => [
                            'text' => $text,
                            'matchType' => $matchType,
                        ],
                    ],
                ];
                $plannedForCampaign++;
            }
        }

        return $operations;
    }

    private function existingNegativeKeywords(GoogleAdsRestClient $ads, string $campaignId): array
    {
        $rows = $ads->searchStream(sprintf(<<<GAQL
SELECT
  campaign_criterion.keyword.text,
  campaign_criterion.keyword.match_type
FROM campaign_criterion
WHERE campaign.id = %s
  AND campaign_criterion.type = KEYWORD
  AND campaign_criterion.negative = TRUE
GAQL, $campaignId));

        $existing = [];
        foreach ($rows as $row) {
            $existing[$this->negativeKey(
                (string) data_get($row, 'campaignCriterion.keyword.text'),
                (string) data_get($row, 'campaignCriterion.keyword.matchType')
            )] = true;
        }

        return $existing;
    }

    private function mutateIfNeeded(
        GoogleAdsRestClient $ads,
        string $resource,
        array $operations,
        bool $apply,
        bool $partialFailure
    ): void {
        if ($operations === []) {
            $this->line($resource . ': no changes needed.');
            return;
        }

        $response = $ads->mutate($resource, $operations, [
            'validateOnly' => ! $apply,
            'partialFailure' => $partialFailure,
            'responseContentType' => 'RESOURCE_NAME_ONLY',
        ]);

        if (! empty($response['partialFailureError'])) {
            throw new \RuntimeException($resource . ' partial failure: ' . json_encode($response['partialFailureError'], JSON_UNESCAPED_UNICODE));
        }

        $this->line(sprintf(
            '%s: %s %d operation(s).',
            $resource,
            $apply ? 'applied' : 'validated',
            count($operations)
        ));
    }

    private function negativeKey(string $text, string $matchType): string
    {
        return strtolower(trim($matchType)) . ':' . strtolower(trim($text));
    }
}
