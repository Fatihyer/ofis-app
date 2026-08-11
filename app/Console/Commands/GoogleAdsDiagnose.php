<?php

namespace App\Console\Commands;

use App\Services\GoogleAdsRestClient;
use DateTimeImmutable;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Throwable;

class GoogleAdsDiagnose extends Command
{
    protected $signature = 'google-ads:diagnose
        {--days=30 : Number of completed days to analyze}
        {--limit=120 : Maximum rows per detailed report}
        {--save : Save the full report as JSON in storage/app/google-ads}';

    protected $description = 'Analyze Google Ads campaigns, keywords and search terms for Paris Via';

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
            'rental car',
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
        'logistics/shipping instead of chauffeured passenger transport' => [
            'logistic',
            'logistics',
            'shipping',
            'freight',
            'cargo',
            'transport company of italy',
            'transport company in spain',
            'transport company spain',
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

    private array $positiveSignals = [
        'chauffeur',
        'with driver',
        'avec chauffeur',
        'private transfer',
        'transfert prive',
        'transfert privé',
        'airport transfer',
        'transfert aeroport',
        'transfert aéroport',
        'minibus',
        'sprinter',
        'van with driver',
        'van hire',
        'classe v',
        'v class',
        'autocar',
        'coach',
        'bus hire',
        'transport groupe',
        'transport de groupe',
        'navette',
    ];

    public function handle(GoogleAdsRestClient $ads): int
    {
        $days = max(1, min(365, (int) $this->option('days')));
        $limit = max(10, min(500, (int) $this->option('limit')));
        $end = new DateTimeImmutable('yesterday');
        $start = $end->modify('-' . ($days - 1) . ' days');

        $this->line(sprintf(
            'Google Ads diagnosis for %s to %s',
            $start->format('Y-m-d'),
            $end->format('Y-m-d')
        ));
        $this->line('Customer ID: ' . $ads->customerId());
        $this->line('Login Customer ID: ' . ($ads->loginCustomerId() ?: 'not set'));

        try {
            $campaigns = $this->campaigns($ads, $start, $end, $limit);
            $keywords = $this->keywords($ads, $start, $end, $limit);
            $searchTerms = $this->searchTerms($ads, $start, $end, $limit);
            $devices = $this->devices($ads, $start, $end);
            $hours = $this->hours($ads, $start, $end);
            $conversionActions = $this->conversionActions($ads, $start, $end);

            $report = $this->buildReport($campaigns, $keywords, $searchTerms, $devices, $hours, $conversionActions, $start, $end);

            $this->printReport($report);

            if ($this->option('save')) {
                $path = 'google-ads/google-ads-diagnosis-' . now()->format('Ymd_His') . '.json';
                Storage::put($path, json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
                $this->newLine();
                $this->info('Saved: storage/app/' . $path);
            }

            return self::SUCCESS;
        } catch (Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }
    }

    private function campaigns(GoogleAdsRestClient $ads, DateTimeImmutable $start, DateTimeImmutable $end, int $limit): array
    {
        return $this->mapRows($ads->searchStream(sprintf(<<<'GAQL'
SELECT
  campaign.id,
  campaign.name,
  campaign.status,
  campaign.advertising_channel_type,
  metrics.impressions,
  metrics.clicks,
  metrics.cost_micros,
  metrics.conversions,
  metrics.conversions_value
FROM campaign
WHERE segments.date BETWEEN '%s' AND '%s'
ORDER BY metrics.cost_micros DESC
LIMIT %d
GAQL, $start->format('Y-m-d'), $end->format('Y-m-d'), $limit)), 'campaign');
    }

    private function keywords(GoogleAdsRestClient $ads, DateTimeImmutable $start, DateTimeImmutable $end, int $limit): array
    {
        return $this->mapRows($ads->searchStream(sprintf(<<<'GAQL'
SELECT
  campaign.name,
  ad_group.name,
  ad_group_criterion.keyword.text,
  ad_group_criterion.keyword.match_type,
  ad_group_criterion.status,
  metrics.impressions,
  metrics.clicks,
  metrics.cost_micros,
  metrics.conversions,
  metrics.conversions_value
FROM keyword_view
WHERE segments.date BETWEEN '%s' AND '%s'
  AND ad_group_criterion.type = KEYWORD
  AND ad_group_criterion.status != REMOVED
ORDER BY metrics.cost_micros DESC
LIMIT %d
GAQL, $start->format('Y-m-d'), $end->format('Y-m-d'), $limit)), 'keyword');
    }

    private function searchTerms(GoogleAdsRestClient $ads, DateTimeImmutable $start, DateTimeImmutable $end, int $limit): array
    {
        return $this->mapRows($ads->searchStream(sprintf(<<<'GAQL'
SELECT
  campaign.name,
  ad_group.name,
  search_term_view.search_term,
  metrics.impressions,
  metrics.clicks,
  metrics.cost_micros,
  metrics.conversions,
  metrics.conversions_value
FROM search_term_view
WHERE segments.date BETWEEN '%s' AND '%s'
ORDER BY metrics.cost_micros DESC
LIMIT %d
GAQL, $start->format('Y-m-d'), $end->format('Y-m-d'), $limit)), 'search_term');
    }

    private function devices(GoogleAdsRestClient $ads, DateTimeImmutable $start, DateTimeImmutable $end): array
    {
        return $this->mapRows($ads->searchStream(sprintf(<<<'GAQL'
SELECT
  segments.device,
  metrics.impressions,
  metrics.clicks,
  metrics.cost_micros,
  metrics.conversions,
  metrics.conversions_value
FROM campaign
WHERE segments.date BETWEEN '%s' AND '%s'
ORDER BY metrics.cost_micros DESC
GAQL, $start->format('Y-m-d'), $end->format('Y-m-d'))), 'device');
    }

    private function hours(GoogleAdsRestClient $ads, DateTimeImmutable $start, DateTimeImmutable $end): array
    {
        return $this->mapRows($ads->searchStream(sprintf(<<<'GAQL'
SELECT
  segments.hour,
  metrics.impressions,
  metrics.clicks,
  metrics.cost_micros,
  metrics.conversions,
  metrics.conversions_value
FROM campaign
WHERE segments.date BETWEEN '%s' AND '%s'
ORDER BY metrics.cost_micros DESC
GAQL, $start->format('Y-m-d'), $end->format('Y-m-d'))), 'hour');
    }

    private function conversionActions(GoogleAdsRestClient $ads, DateTimeImmutable $start, DateTimeImmutable $end): array
    {
        return $this->mapRows($ads->searchStream(sprintf(<<<'GAQL'
SELECT
  segments.conversion_action_name,
  metrics.conversions,
  metrics.all_conversions
FROM customer
WHERE segments.date BETWEEN '%s' AND '%s'
ORDER BY metrics.conversions DESC
LIMIT 30
GAQL, $start->format('Y-m-d'), $end->format('Y-m-d'))), 'conversion_action');
    }

    private function mapRows(array $rows, string $type): array
    {
        return array_map(function (array $row) use ($type): array {
            $cost = ((int) data_get($row, 'metrics.costMicros', 0)) / 1000000;
            $clicks = (int) data_get($row, 'metrics.clicks', 0);
            $impressions = (int) data_get($row, 'metrics.impressions', 0);
            $conversions = (float) data_get($row, 'metrics.conversions', 0);

            $mapped = [
                'impressions' => $impressions,
                'clicks' => $clicks,
                'cost' => round($cost, 2),
                'conversions' => round($conversions, 2),
                'conversion_value' => round((float) data_get($row, 'metrics.conversionsValue', 0), 2),
                'ctr' => $impressions > 0 ? round(($clicks / $impressions) * 100, 2) : 0.0,
                'cpc' => $clicks > 0 ? round($cost / $clicks, 2) : 0.0,
                'cpa' => $conversions > 0 ? round($cost / $conversions, 2) : null,
            ];

            if ($type === 'campaign') {
                return $mapped + [
                    'id' => data_get($row, 'campaign.id'),
                    'name' => data_get($row, 'campaign.name'),
                    'status' => data_get($row, 'campaign.status'),
                    'channel' => data_get($row, 'campaign.advertisingChannelType'),
                ];
            }

            if ($type === 'keyword') {
                return $mapped + [
                    'campaign' => data_get($row, 'campaign.name'),
                    'ad_group' => data_get($row, 'adGroup.name'),
                    'text' => data_get($row, 'adGroupCriterion.keyword.text'),
                    'match_type' => data_get($row, 'adGroupCriterion.keyword.matchType'),
                    'status' => data_get($row, 'adGroupCriterion.status'),
                ];
            }

            if ($type === 'search_term') {
                $term = (string) data_get($row, 'searchTermView.searchTerm');

                return $mapped + [
                    'campaign' => data_get($row, 'campaign.name'),
                    'ad_group' => data_get($row, 'adGroup.name'),
                    'term' => $term,
                    'classification' => $this->classifyTerm($term),
                ];
            }

            if ($type === 'device') {
                return $mapped + [
                    'device' => data_get($row, 'segments.device'),
                ];
            }

            if ($type === 'conversion_action') {
                return [
                    'name' => data_get($row, 'segments.conversionActionName'),
                    'conversions' => round((float) data_get($row, 'metrics.conversions', 0), 2),
                    'all_conversions' => round((float) data_get($row, 'metrics.allConversions', 0), 2),
                ];
            }

            return $mapped + [
                'hour' => data_get($row, 'segments.hour'),
            ];
        }, $rows);
    }

    private function buildReport(
        array $campaigns,
        array $keywords,
        array $searchTerms,
        array $devices,
        array $hours,
        array $conversionActions,
        DateTimeImmutable $start,
        DateTimeImmutable $end
    ): array {
        $totals = $this->totals($campaigns);

        $wastedTerms = array_values(array_filter($searchTerms, fn (array $row): bool => $row['cost'] >= 2 && $row['conversions'] <= 0));
        usort($wastedTerms, fn (array $a, array $b): int => $b['cost'] <=> $a['cost']);

        $negativeCandidates = array_values(array_filter($searchTerms, fn (array $row): bool => $row['cost'] >= 0.5 && $row['classification']['negative'] !== null));
        usort($negativeCandidates, fn (array $a, array $b): int => $b['cost'] <=> $a['cost']);

        $strongTerms = array_values(array_filter($searchTerms, fn (array $row): bool => $row['classification']['positive'] && $row['classification']['negative'] === null));
        usort($strongTerms, fn (array $a, array $b): int => [$b['clicks'], $b['cost']] <=> [$a['clicks'], $a['cost']]);

        $weakKeywords = array_values(array_filter($keywords, fn (array $row): bool => $row['cost'] >= 5 && $row['conversions'] <= 0));
        usort($weakKeywords, fn (array $a, array $b): int => $b['cost'] <=> $a['cost']);

        $bestHours = array_values(array_filter($hours, fn (array $row): bool => $row['conversions'] > 0));
        usort($bestHours, fn (array $a, array $b): int => ($a['cpa'] ?? 999999) <=> ($b['cpa'] ?? 999999));

        return [
            'range' => [
                'start' => $start->format('Y-m-d'),
                'end' => $end->format('Y-m-d'),
            ],
            'totals' => $totals,
            'campaigns' => $campaigns,
            'search_terms' => $searchTerms,
            'keywords' => $keywords,
            'devices' => $devices,
            'hours' => $hours,
            'conversion_actions' => $conversionActions,
            'recommendations' => [
                'negative_search_terms' => array_slice($negativeCandidates, 0, 25),
                'wasted_search_terms' => array_slice($wastedTerms, 0, 25),
                'strong_search_terms' => array_slice($strongTerms, 0, 25),
                'weak_keywords' => array_slice($weakKeywords, 0, 25),
                'best_hours' => array_slice($bestHours, 0, 10),
            ],
        ];
    }

    private function printReport(array $report): void
    {
        $this->newLine();
        $this->info('Account summary');
        $this->table(['Cost', 'Impr.', 'Clicks', 'CTR', 'Avg CPC', 'Conv.', 'CPA'], [[
            number_format($report['totals']['cost'], 2),
            $report['totals']['impressions'],
            $report['totals']['clicks'],
            $report['totals']['ctr'] . '%',
            number_format($report['totals']['cpc'], 2),
            $report['totals']['conversions'],
            $report['totals']['cpa'] === null ? '-' : number_format($report['totals']['cpa'], 2),
        ]]);

        $this->info('Campaigns');
        $this->table(['Name', 'Status', 'Cost', 'Clicks', 'Conv.', 'CPA'], array_map(fn (array $row): array => [
            $row['name'],
            $row['status'],
            number_format($row['cost'], 2),
            $row['clicks'],
            $row['conversions'],
            $row['cpa'] === null ? '-' : number_format($row['cpa'], 2),
        ], array_slice($report['campaigns'], 0, 10)));

        $this->info('Conversion actions');
        $this->table(['Name', 'Conversions', 'All conv.'], array_map(fn (array $row): array => [
            $row['name'],
            $row['conversions'],
            $row['all_conversions'],
        ], $report['conversion_actions']));

        $this->info('Negative search term candidates');
        $this->table(['Term', 'Reason', 'Cost', 'Clicks'], array_map(fn (array $row): array => [
            $row['term'],
            $row['classification']['negative'],
            number_format($row['cost'], 2),
            $row['clicks'],
        ], $report['recommendations']['negative_search_terms']));

        $this->info('High cost, no conversion search terms');
        $this->table(['Term', 'Cost', 'Clicks', 'CTR'], array_map(fn (array $row): array => [
            $row['term'],
            number_format($row['cost'], 2),
            $row['clicks'],
            $row['ctr'] . '%',
        ], array_slice($report['recommendations']['wasted_search_terms'], 0, 12)));

        $this->info('Strong search terms to consider as keywords');
        $this->table(['Term', 'Cost', 'Clicks', 'Conv.', 'CPA'], array_map(fn (array $row): array => [
            $row['term'],
            number_format($row['cost'], 2),
            $row['clicks'],
            $row['conversions'],
            $row['cpa'] === null ? '-' : number_format($row['cpa'], 2),
        ], $report['recommendations']['strong_search_terms']));

        $this->info('Weak keywords');
        $this->table(['Keyword', 'Match', 'Cost', 'Clicks', 'Conv.'], array_map(fn (array $row): array => [
            $row['text'],
            $row['match_type'],
            number_format($row['cost'], 2),
            $row['clicks'],
            $row['conversions'],
        ], array_slice($report['recommendations']['weak_keywords'], 0, 12)));
    }

    private function totals(array $rows): array
    {
        $cost = array_sum(array_column($rows, 'cost'));
        $clicks = array_sum(array_column($rows, 'clicks'));
        $impressions = array_sum(array_column($rows, 'impressions'));
        $conversions = array_sum(array_column($rows, 'conversions'));

        return [
            'cost' => round($cost, 2),
            'clicks' => $clicks,
            'impressions' => $impressions,
            'conversions' => round($conversions, 2),
            'ctr' => $impressions > 0 ? round(($clicks / $impressions) * 100, 2) : 0.0,
            'cpc' => $clicks > 0 ? round($cost / $clicks, 2) : 0.0,
            'cpa' => $conversions > 0 ? round($cost / $conversions, 2) : null,
        ];
    }

    private function classifyTerm(string $term): array
    {
        $normalized = $this->normalize($term);
        $explicitDriverService = str_contains($normalized, 'chauffeur')
            || str_contains($normalized, 'with driver')
            || str_contains($normalized, 'avec chauffeur');

        foreach ($this->negativePatterns as $reason => $patterns) {
            foreach ($patterns as $pattern) {
                if (str_contains($normalized, $this->normalize($pattern))) {
                    if ($reason === 'self-drive/rental without chauffeur' && $explicitDriverService) {
                        continue;
                    }

                    return [
                        'negative' => $reason,
                        'positive' => false,
                    ];
                }
            }
        }

        foreach ($this->positiveSignals as $signal) {
            if (str_contains($normalized, $this->normalize($signal))) {
                return [
                    'negative' => null,
                    'positive' => true,
                ];
            }
        }

        return [
            'negative' => null,
            'positive' => false,
        ];
    }

    private function normalize(string $value): string
    {
        $value = strtolower($value);
        $ascii = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);

        return $ascii !== false ? $ascii : $value;
    }
}
