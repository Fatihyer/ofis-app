<?php

namespace App\Http\Controllers;

use App\Services\GoogleAdsFormLeadConversionUploader;
use App\Services\GoogleAdsRestClient;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class GoogleAdsDashboardController extends Controller
{
    private const SHARED_NEGATIVE_SET_NAME = 'Paris Via - Shared Negative Keywords';

    private array $negativePatterns = [
        'Location voiture / sans chauffeur' => [
            'sans chauffeur',
            'self drive',
            'self-drive',
            'rent a car',
            'car rental',
            'location voiture',
            'location de voiture',
            'voiture de location',
            'rent car',
            'car rent',
            'budget',
            'drivalia',
            'hertz',
            'sixt',
            'europcar',
            'enterprise',
        ],
        'Transport public / billets' => [
            'ratp',
            'navigo',
            'metro',
            'métro',
            'ticket',
            'billet',
            'horaires',
            'ligne bus',
            'big bus',
            'flixbus',
            'blablabus',
            'tgv',
            'train',
            'tren',
            'cheapest way',
        ],
        'Logistique / cargo' => [
            'logistic',
            'logistics',
            'shipping',
            'freight',
            'cargo',
            'fm logistic',
            'romasco',
            'top 10 logistics',
        ],
        'Hors zone Paris' => [
            'marseille',
            'lyon airport',
            'nantes airport',
            'belgium',
            'italy',
            'spain',
            'luxembourg',
            'kiem transports lux',
        ],
        'Info aeroport seulement' => [
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

    public function index(GoogleAdsRestClient $ads): View
    {
        $range = [
            'start' => now('Europe/Paris')->subDays(29)->toDateString(),
            'end' => now('Europe/Paris')->toDateString(),
        ];

        return view('google_ads.index', [
            'range' => $range,
            'customerId' => $ads->customerId(),
            'loginCustomerId' => $ads->loginCustomerId(),
            'apiVersion' => $ads->apiVersion(),
            'connection' => $this->connection($ads),
            'campaigns' => $this->campaigns($ads, $range),
            'conversionActions' => $this->conversionActions($ads),
            'uploadCounts' => $this->uploadCounts(),
            'leadCounts' => $this->leadCounts(),
            'leadConversionStatus' => $this->leadConversionStatus(),
            'leadQuality' => $this->leadQualityReport(),
            'recentLeads' => $this->recentLeads(),
            'lastUploads' => $this->lastUploads(),
            'searchTermReport' => $this->searchTermReport($ads, $range),
            'keywordReport' => $this->keywordReport($ads, $range),
            'sharedNegativeList' => $this->sharedNegativeListStatus($ads),
            'adsPlan' => $this->adsActionPlan(),
        ]);
    }

    public function refresh(): RedirectResponse
    {
        $this->clearDashboardCache();

        return redirect()
            ->route('google-ads.index')
            ->with('google_ads_message', 'Données Google Ads actualisées.');
    }

    public function uploadFormLeads(Request $request, GoogleAdsFormLeadConversionUploader $uploader): RedirectResponse
    {
        $summary = $uploader->uploadPending(25);

        return redirect()
            ->route('google-ads.index')
            ->with('google_ads_upload_summary', $summary);
    }

    public function addNegativeKeyword(Request $request, GoogleAdsRestClient $ads): RedirectResponse
    {
        $data = $request->validate([
            'campaign_id' => 'required|string|max:30',
            'term' => 'required|string|max:255',
            'match_type' => 'required|in:EXACT,PHRASE',
        ]);

        try {
            $sharedSet = $this->findOrCreateSharedNegativeSet($ads);
            $campaign = $this->campaignResource($ads, $data['campaign_id']);
            $term = trim($data['term']);
            $matchType = $data['match_type'];

            $this->attachSharedSetToCampaign($ads, $sharedSet['resource_name'], $campaign);

            if ($this->sharedNegativeKeywordExists($ads, $sharedSet['resource_name'], $term, $matchType)) {
                return redirect()
                    ->route('google-ads.index')
                    ->with('google_ads_message', 'Déjà présent dans la liste partagée: ' . $term);
            }

            $ads->mutate('sharedCriteria', [[
                'create' => [
                    'sharedSet' => $sharedSet['resource_name'],
                    'keyword' => [
                        'text' => $term,
                        'matchType' => $matchType,
                    ],
                ],
            ]], [
                'partialFailure' => false,
                'responseContentType' => 'RESOURCE_NAME_ONLY',
            ]);

            $removedDuplicate = $this->removeCampaignNegativeIfExists($ads, $campaign['id'], $term, $matchType);
            $this->clearDashboardCache();

            return redirect()
                ->route('google-ads.index')
                ->with('google_ads_message', 'Ajouté à la liste partagée: ' . $term . ($removedDuplicate ? ' · doublon campagne supprimé' : ''));
        } catch (\Throwable $e) {
            return redirect()
                ->route('google-ads.index')
                ->with('google_ads_error', $e->getMessage());
        }
    }

    public function setupSharedNegativeList(Request $request, GoogleAdsRestClient $ads): RedirectResponse
    {
        try {
            $sharedSet = $this->findOrCreateSharedNegativeSet($ads);
            $campaigns = $this->enabledSearchCampaigns($ads);
            $attached = 0;

            foreach ($campaigns as $campaign) {
                if ($this->attachSharedSetToCampaign($ads, $sharedSet['resource_name'], $campaign)) {
                    $attached++;
                }
            }

            $this->clearDashboardCache();

            return redirect()
                ->route('google-ads.index')
                ->with('google_ads_message', sprintf(
                    'Liste partagée prête: %s · %d campagne(s) attachée(s).',
                    self::SHARED_NEGATIVE_SET_NAME,
                    $attached
                ));
        } catch (\Throwable $e) {
            return redirect()
                ->route('google-ads.index')
                ->with('google_ads_error', $e->getMessage());
        }
    }

    public function applyNegativeCandidates(): RedirectResponse
    {
        try {
            $exitCode = Artisan::call('google-ads:apply-negative-candidates', [
                '--days' => 30,
                '--limit' => 50,
                '--min-cost' => 0.5,
                '--apply' => true,
            ]);

            $this->clearDashboardCache();

            return redirect()
                ->route('google-ads.index')
                ->with(
                    $exitCode === 0 ? 'google_ads_message' : 'google_ads_error',
                    trim(Artisan::output()) ?: 'Traitement terminé.'
                );
        } catch (\Throwable $e) {
            return redirect()
                ->route('google-ads.index')
                ->with('google_ads_error', $e->getMessage());
        }
    }

    public function cleanRedundantNegatives(): RedirectResponse
    {
        try {
            $exitCode = Artisan::call('google-ads:clean-negative-keywords', [
                '--limit' => 500,
                '--apply' => true,
            ]);

            $this->clearDashboardCache();

            return redirect()
                ->route('google-ads.index')
                ->with(
                    $exitCode === 0 ? 'google_ads_message' : 'google_ads_error',
                    trim(Artisan::output()) ?: 'Nettoyage terminé.'
                );
        } catch (\Throwable $e) {
            return redirect()
                ->route('google-ads.index')
                ->with('google_ads_error', $e->getMessage());
        }
    }

    private function connection(GoogleAdsRestClient $ads): array
    {
        try {
            return [
                'ok' => true,
                'customers' => $ads->listAccessibleCustomers(),
                'error' => null,
            ];
        } catch (\Throwable $e) {
            return [
                'ok' => false,
                'customers' => [],
                'error' => $e->getMessage(),
            ];
        }
    }

    private function campaigns(GoogleAdsRestClient $ads, array $range): array
    {
        return $this->cachedAdsReport('campaigns', $range, function () use ($ads, $range) {
            $rows = $ads->searchStream(sprintf(<<<'GAQL'
SELECT
  campaign.id,
  campaign.resource_name,
  campaign.name,
  campaign.status,
  metrics.impressions,
  metrics.clicks,
  metrics.cost_micros,
  metrics.conversions
FROM campaign
WHERE segments.date BETWEEN '%s' AND '%s'
ORDER BY metrics.cost_micros DESC
LIMIT 10
GAQL, $range['start'], $range['end']));

            return array_map(function (array $row): array {
                $cost = ((int) data_get($row, 'metrics.costMicros', 0)) / 1000000;
                $clicks = (int) data_get($row, 'metrics.clicks', 0);
                $impressions = (int) data_get($row, 'metrics.impressions', 0);
                $conversions = (float) data_get($row, 'metrics.conversions', 0);

                return [
                    'id' => data_get($row, 'campaign.id'),
                    'resource_name' => data_get($row, 'campaign.resourceName'),
                    'name' => data_get($row, 'campaign.name'),
                    'status' => data_get($row, 'campaign.status'),
                    'impressions' => $impressions,
                    'clicks' => $clicks,
                    'cost' => round($cost, 2),
                    'conversions' => round($conversions, 2),
                    'ctr' => $impressions > 0 ? round(($clicks / $impressions) * 100, 2) : 0,
                    'cpc' => $clicks > 0 ? round($cost / $clicks, 2) : 0,
                ];
            }, $rows);
        });
    }

    private function conversionActions(GoogleAdsRestClient $ads): array
    {
        return $this->cachedAdsReport('conversion-actions', ['start' => 'all', 'end' => 'all'], function () use ($ads) {
            $rows = $ads->searchStream(<<<'GAQL'
SELECT
  conversion_action.id,
  conversion_action.name,
  conversion_action.type,
  conversion_action.status,
  conversion_action.primary_for_goal
FROM conversion_action
WHERE conversion_action.status != REMOVED
ORDER BY conversion_action.name
GAQL);

            return array_map(fn (array $row): array => [
                'id' => data_get($row, 'conversionAction.id'),
                'name' => data_get($row, 'conversionAction.name'),
                'type' => data_get($row, 'conversionAction.type'),
                'status' => data_get($row, 'conversionAction.status'),
                'primary' => (bool) data_get($row, 'conversionAction.primaryForGoal'),
            ], $rows);
        });
    }

    private function searchTermReport(GoogleAdsRestClient $ads, array $range): array
    {
        return $this->cachedAdsReport('search-terms', $range, function () use ($ads, $range) {
            $rows = $ads->searchStream(sprintf(<<<'GAQL'
SELECT
  campaign.id,
  campaign.name,
  ad_group.name,
  search_term_view.search_term,
  metrics.impressions,
  metrics.clicks,
  metrics.cost_micros,
  metrics.conversions
FROM search_term_view
WHERE segments.date BETWEEN '%s' AND '%s'
ORDER BY metrics.cost_micros DESC
LIMIT 120
GAQL, $range['start'], $range['end']));

            $terms = array_map(function (array $row): array {
                $cost = ((int) data_get($row, 'metrics.costMicros', 0)) / 1000000;
                $clicks = (int) data_get($row, 'metrics.clicks', 0);
                $impressions = (int) data_get($row, 'metrics.impressions', 0);
                $term = (string) data_get($row, 'searchTermView.searchTerm');

                return [
                    'campaign' => data_get($row, 'campaign.name'),
                    'campaign_id' => data_get($row, 'campaign.id'),
                    'ad_group' => data_get($row, 'adGroup.name'),
                    'term' => $term,
                    'impressions' => $impressions,
                    'clicks' => $clicks,
                    'cost' => round($cost, 2),
                    'conversions' => round((float) data_get($row, 'metrics.conversions', 0), 2),
                    'ctr' => $impressions > 0 ? round(($clicks / $impressions) * 100, 2) : 0,
                    'classification' => $this->classifyTerm($term),
                ];
            }, $rows);

            $negative = array_values(array_filter($terms, fn (array $row): bool => $row['cost'] >= 0.5 && $row['classification']['negative']));
            usort($negative, fn (array $a, array $b): int => $b['cost'] <=> $a['cost']);

            $opportunities = array_values(array_filter($terms, fn (array $row): bool => $row['classification']['positive'] && ! $row['classification']['negative']));
            usort($opportunities, fn (array $a, array $b): int => [$b['clicks'], $b['cost']] <=> [$a['clicks'], $a['cost']]);

            $waste = array_values(array_filter($terms, fn (array $row): bool => $row['cost'] >= 2 && $row['conversions'] <= 0));
            usort($waste, fn (array $a, array $b): int => $b['cost'] <=> $a['cost']);

            return [
                'all' => $terms,
                'negative' => array_slice($negative, 0, 25),
                'opportunities' => array_slice($opportunities, 0, 25),
                'waste' => array_slice($waste, 0, 25),
            ];
        });
    }

    private function keywordReport(GoogleAdsRestClient $ads, array $range): array
    {
        return $this->cachedAdsReport('keywords', $range, function () use ($ads, $range) {
            $rows = $ads->searchStream(sprintf(<<<'GAQL'
SELECT
  campaign.name,
  ad_group.name,
  ad_group_criterion.keyword.text,
  ad_group_criterion.keyword.match_type,
  ad_group_criterion.status,
  metrics.impressions,
  metrics.clicks,
  metrics.cost_micros,
  metrics.conversions
FROM keyword_view
WHERE segments.date BETWEEN '%s' AND '%s'
  AND ad_group_criterion.type = KEYWORD
  AND ad_group_criterion.status != REMOVED
ORDER BY metrics.cost_micros DESC
LIMIT 120
GAQL, $range['start'], $range['end']));

            $keywords = array_map(function (array $row): array {
                $cost = ((int) data_get($row, 'metrics.costMicros', 0)) / 1000000;

                return [
                    'campaign' => data_get($row, 'campaign.name'),
                    'ad_group' => data_get($row, 'adGroup.name'),
                    'text' => data_get($row, 'adGroupCriterion.keyword.text'),
                    'match_type' => data_get($row, 'adGroupCriterion.keyword.matchType'),
                    'status' => data_get($row, 'adGroupCriterion.status'),
                    'clicks' => (int) data_get($row, 'metrics.clicks', 0),
                    'cost' => round($cost, 2),
                    'conversions' => round((float) data_get($row, 'metrics.conversions', 0), 2),
                ];
            }, $rows);

            $weak = array_values(array_filter($keywords, fn (array $row): bool => $row['cost'] >= 5 && $row['conversions'] <= 0));
            usort($weak, fn (array $a, array $b): int => $b['cost'] <=> $a['cost']);

            return [
                'all' => $keywords,
                'weak' => array_slice($weak, 0, 25),
            ];
        });
    }

    private function cachedAdsReport(string $name, array $range, callable $callback): array
    {
        $key = sprintf('google_ads_dashboard_%s_%s_%s', $name, $range['start'], $range['end']);

        try {
            return Cache::remember($key, now()->addMinutes(10), $callback);
        } catch (\Throwable $e) {
            return [
                [
                    'error' => $e->getMessage(),
                ],
            ];
        }
    }

    private function uploadCounts(): array
    {
        return DB::table('google_ads_conversion_uploads')
            ->select('upload_status', DB::raw('COUNT(*) as count'))
            ->groupBy('upload_status')
            ->orderBy('upload_status')
            ->pluck('count', 'upload_status')
            ->toArray();
    }

    private function leadCounts(): array
    {
        return [
            'total' => DB::table('google_ads_leads')->count(),
            'with_click_id' => DB::table('google_ads_leads')
                ->where(function ($query) {
                    $query->whereNotNull('gclid')
                        ->orWhereNotNull('gbraid')
                        ->orWhereNotNull('wbraid');
                })
                ->count(),
            'today' => DB::table('google_ads_leads')
                ->where('created_at', '>=', Carbon::today('Europe/Paris'))
                ->count(),
        ];
    }

    private function leadConversionStatus(): array
    {
        $definitions = $this->leadConversionStatusDefinitions();
        $counts = array_fill_keys(array_keys($definitions), 0);

        $leads = DB::table('google_ads_leads as l')
            ->leftJoin('google_ads_conversion_uploads as u', 'u.google_ads_lead_id', '=', 'l.id')
            ->select('l.gclid', 'l.gbraid', 'l.wbraid', 'l.sync_status', 'u.upload_status')
            ->get();

        foreach ($leads as $lead) {
            $counts[$this->leadConversionState($lead)]++;
        }

        $total = $leads->count();

        return collect($definitions)
            ->map(function (array $definition, string $key) use ($counts, $total): array {
                return array_merge($definition, [
                    'key' => $key,
                    'count' => $counts[$key] ?? 0,
                    'percent' => $total > 0 ? round((($counts[$key] ?? 0) / $total) * 100, 1) : 0,
                ]);
            })
            ->filter(fn (array $status): bool => $status['key'] !== 'other' || $status['count'] > 0)
            ->values()
            ->all();
    }

    private function leadConversionStatusDefinitions(): array
    {
        return [
            'uploaded' => [
                'label' => 'Envoyé',
                'title' => 'Envoyé à Google Ads',
                'tone' => 'success',
                'hint' => 'Conversion offline acceptée par Google Ads.',
                'action' => 'OK.',
            ],
            'missing_click_id' => [
                'label' => 'Sans click ID',
                'title' => 'Impossible à envoyer',
                'tone' => 'warning',
                'hint' => 'Lead reçu sans gclid, gbraid ou wbraid.',
                'action' => 'Vérifier source, landing page et consentement.',
            ],
            'pending' => [
                'label' => 'En attente',
                'title' => 'Prêt à envoyer',
                'tone' => 'primary',
                'hint' => 'Le prochain envoi automatique ou manuel doit le traiter.',
                'action' => 'Envoyer les leads en attente.',
            ],
            'failed' => [
                'label' => 'Erreur',
                'title' => 'À corriger',
                'tone' => 'danger',
                'hint' => 'Google Ads a refusé l’envoi ou une erreur technique est survenue.',
                'action' => 'Lire l’erreur puis relancer.',
            ],
            'other' => [
                'label' => 'Autre',
                'title' => 'À vérifier',
                'tone' => 'secondary',
                'hint' => 'Statut non classé automatiquement.',
                'action' => 'Contrôler le statut brut.',
            ],
        ];
    }

    private function leadConversionState(object $lead): string
    {
        $uploadStatus = strtolower((string) ($lead->upload_status ?? ''));
        $syncStatus = strtolower((string) ($lead->sync_status ?? ''));

        if ($uploadStatus === 'uploaded' || $syncStatus === 'uploaded') {
            return 'uploaded';
        }

        if ($uploadStatus === 'failed' || in_array($syncStatus, ['failed', 'upload_failed'], true)) {
            return 'failed';
        }

        if ($uploadStatus === 'pending' || $syncStatus === 'pending') {
            return 'pending';
        }

        if ($syncStatus === 'skipped_no_click_id' || ! $this->hasLeadClickId($lead)) {
            return 'missing_click_id';
        }

        return 'other';
    }

    private function leadConversionStateMeta(object $lead): array
    {
        $key = $this->leadConversionState($lead);

        return array_merge(['key' => $key], $this->leadConversionStatusDefinitions()[$key]);
    }

    private function hasLeadClickId(object $lead): bool
    {
        foreach (['gclid', 'gbraid', 'wbraid'] as $field) {
            if (trim((string) ($lead->{$field} ?? '')) !== '') {
                return true;
            }
        }

        return false;
    }

    private function leadQualityReport(): array
    {
        $start = now('Europe/Paris')->subDays(29)->startOfDay();
        $base = fn () => DB::table('google_ads_leads as l')
            ->leftJoin('talepler as t', 't.id', '=', 'l.talep_id')
            ->where('l.created_at', '>=', $start);

        $summary = [
            'range_label' => '30 derniers jours',
            'total' => $base()->count('l.id'),
            'with_click_id' => $base()->where($this->clickIdWhere())->count('l.id'),
            'uploaded' => $base()->where('l.sync_status', 'uploaded')->count('l.id'),
            'pending_upload' => $base()->whereIn('l.sync_status', ['pending', 'upload_failed'])->count('l.id'),
            'confirmed' => $base()->whereRaw($this->confirmedLeadSql())->count('l.id'),
            'lost' => $base()->whereRaw($this->lostLeadSql())->count('l.id'),
            'quoted_value' => round((float) $base()->sum(DB::raw($this->leadValueSql())), 2),
        ];

        $summary['click_id_rate'] = $summary['total'] > 0 ? round(($summary['with_click_id'] / $summary['total']) * 100, 1) : 0;
        $summary['confirm_rate'] = $summary['total'] > 0 ? round(($summary['confirmed'] / $summary['total']) * 100, 1) : 0;
        $summary['avg_value'] = $summary['total'] > 0 ? round($summary['quoted_value'] / $summary['total'], 2) : 0;

        return [
            'summary' => $summary,
            'campaigns' => $this->leadQualityGroups("COALESCE(NULLIF(l.utm_campaign, ''), '(unknown campaign)')", 10),
            'terms' => $this->leadQualityGroups("COALESCE(NULLIF(l.utm_term, ''), '(unknown keyword)')", 10),
            'landing_pages' => $this->leadQualityGroups("COALESCE(NULLIF(l.landing_page, ''), NULLIF(l.page_url, ''), '(unknown landing)')", 8),
            'services' => $this->leadQualityGroups("COALESCE(NULLIF(l.service_type, ''), NULLIF(t.service_type, ''), '(unknown service)')", 8),
            'vehicles' => $this->leadQualityGroups("COALESCE(NULLIF(l.vehicle_type, ''), NULLIF(t.vehicle_type, ''), '(unknown vehicle)')", 8),
        ];
    }

    private function leadQualityGroups(string $labelSql, int $limit): \Illuminate\Support\Collection
    {
        return DB::table('google_ads_leads as l')
            ->leftJoin('talepler as t', 't.id', '=', 'l.talep_id')
            ->where('l.created_at', '>=', now('Europe/Paris')->subDays(29)->startOfDay())
            ->selectRaw($labelSql . ' as label')
            ->selectRaw('COUNT(l.id) as total')
            ->selectRaw('SUM(CASE WHEN ' . $this->clickIdSql() . ' THEN 1 ELSE 0 END) as with_click_id')
            ->selectRaw("SUM(CASE WHEN l.sync_status = 'uploaded' THEN 1 ELSE 0 END) as uploaded")
            ->selectRaw('SUM(CASE WHEN ' . $this->confirmedLeadSql() . ' THEN 1 ELSE 0 END) as confirmed')
            ->selectRaw('SUM(CASE WHEN ' . $this->lostLeadSql() . ' THEN 1 ELSE 0 END) as lost')
            ->selectRaw('SUM(' . $this->leadValueSql() . ') as quoted_value')
            ->groupBy('label')
            ->orderByDesc('total')
            ->limit($limit)
            ->get()
            ->map(function ($row) {
                $row->quoted_value = round((float) $row->quoted_value, 2);
                $row->confirm_rate = (int) $row->total > 0 ? round(((int) $row->confirmed / (int) $row->total) * 100, 1) : 0;
                $row->click_id_rate = (int) $row->total > 0 ? round(((int) $row->with_click_id / (int) $row->total) * 100, 1) : 0;

                return $row;
            });
    }

    private function clickIdWhere(): callable
    {
        return function ($query) {
            $query->whereNotNull('l.gclid')
                ->orWhereNotNull('l.gbraid')
                ->orWhereNotNull('l.wbraid');
        };
    }

    private function clickIdSql(): string
    {
        return '(l.gclid IS NOT NULL OR l.gbraid IS NOT NULL OR l.wbraid IS NOT NULL)';
    }

    private function confirmedLeadSql(): string
    {
        return "(t.confirmed_at IS NOT NULL OR t.converted_transfer_id IS NOT NULL OR LOWER(COALESCE(t.konfirme_durumu, '')) REGEXP 'confirm|konfirm|accept|valide|ok')";
    }

    private function lostLeadSql(): string
    {
        return "(LOWER(COALESCE(t.konfirme_durumu, '')) REGEXP 'annul|cancel|refus|perdu|lost')";
    }

    private function leadValueSql(): string
    {
        return 'COALESCE(t.final_total, t.confirmed_price, t.verilen_fiyat, t.system_total, 0)';
    }

    private function recentLeads()
    {
        return DB::table('google_ads_leads as l')
            ->leftJoin('google_ads_conversion_uploads as u', 'u.google_ads_lead_id', '=', 'l.id')
            ->select(
                'l.id',
                'l.created_at',
                'l.talep_id',
                'l.customer_name',
                'l.customer_phone',
                'l.customer_email',
                'l.gclid',
                'l.gbraid',
                'l.wbraid',
                'l.utm_source',
                'l.utm_medium',
                'l.utm_campaign',
                'l.sync_status',
                'l.sync_error',
                'u.upload_status',
                'u.upload_attempts',
                'u.last_attempt_at',
                'u.error_message'
            )
            ->orderByDesc('l.id')
            ->limit(20)
            ->get()
            ->map(function ($lead) {
                $meta = $this->leadConversionStateMeta($lead);

                $lead->conversion_status_key = $meta['key'];
                $lead->conversion_status_label = $meta['label'];
                $lead->conversion_status_title = $meta['title'];
                $lead->conversion_status_tone = $meta['tone'];
                $lead->conversion_status_action = $meta['action'];

                return $lead;
            });
    }

    private function lastUploads()
    {
        return DB::table('google_ads_conversion_uploads as u')
            ->join('google_ads_leads as l', 'l.id', '=', 'u.google_ads_lead_id')
            ->select('u.id', 'u.google_ads_lead_id', 'u.upload_status', 'u.upload_attempts', 'u.last_attempt_at', 'u.uploaded_at', 'u.error_message', 'l.talep_id')
            ->orderByDesc('u.id')
            ->limit(10)
            ->get();
    }

    private function classifyTerm(string $term): array
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

                if ($reason === 'Location voiture / sans chauffeur' && $explicitDriverService) {
                    continue;
                }

                return [
                    'negative' => $reason,
                    'positive' => false,
                ];
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

    private function campaignResource(GoogleAdsRestClient $ads, string $campaignId): array
    {
        $campaignId = preg_replace('/\D+/', '', $campaignId) ?: '';
        if ($campaignId === '') {
            throw new \RuntimeException('Campaign ID invalide.');
        }

        $rows = $ads->searchStream(sprintf(<<<GAQL
SELECT
  campaign.id,
  campaign.resource_name,
  campaign.name
FROM campaign
WHERE campaign.id = %s
LIMIT 1
GAQL, $campaignId));

        $campaign = $rows[0] ?? null;
        if (! $campaign) {
            throw new \RuntimeException('Campagne introuvable.');
        }

        return [
            'id' => data_get($campaign, 'campaign.id'),
            'name' => data_get($campaign, 'campaign.name'),
            'resource_name' => data_get($campaign, 'campaign.resourceName'),
        ];
    }

    private function negativeKeywordExists(GoogleAdsRestClient $ads, string $campaignId, string $term, string $matchType): bool
    {
        $campaignId = preg_replace('/\D+/', '', $campaignId) ?: '';
        $escapedTerm = str_replace("'", "\\'", $term);

        $rows = $ads->searchStream(sprintf(<<<GAQL
SELECT
  campaign_criterion.keyword.text,
  campaign_criterion.keyword.match_type
FROM campaign_criterion
WHERE campaign.id = %s
  AND campaign_criterion.type = KEYWORD
  AND campaign_criterion.negative = TRUE
  AND campaign_criterion.keyword.text = '%s'
  AND campaign_criterion.keyword.match_type = %s
LIMIT 1
GAQL, $campaignId, $escapedTerm, $matchType));

        return $rows !== [];
    }

    private function negativeKeywordCount(GoogleAdsRestClient $ads, string $campaignId): int
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

    private function clearDashboardCache(): void
    {
        $range = [
            'start' => now('Europe/Paris')->subDays(29)->toDateString(),
            'end' => now('Europe/Paris')->toDateString(),
        ];

        foreach (['campaigns', 'conversion-actions', 'search-terms', 'keywords', 'shared-negative-list'] as $name) {
            Cache::forget(sprintf('google_ads_dashboard_%s_%s_%s', $name, $range['start'], $range['end']));
        }

        Cache::forget('google_ads_dashboard_conversion-actions_all_all');
        Cache::forget('google_ads_dashboard_shared-negative-list_all_all');
    }

    private function adsActionPlan(): array
    {
        return [
            'structure' => [
                [
                    'campaign' => 'Search FR - Chauffeur / Groupe Paris',
                    'groups' => 'Transfert aeroport, van avec chauffeur, minibus groupe, autocar evenement',
                    'goal' => 'Separer les intentions FR a forte valeur et exclure self-drive/location voiture.',
                ],
                [
                    'campaign' => 'Search EN - Private Group Transport Paris',
                    'groups' => 'airport transfer, van with driver, sprinter hire, bus coach hire',
                    'goal' => 'Capter touristes, corporate et agences anglophones avec annonces dediees.',
                ],
                [
                    'campaign' => 'Search ES - Traslados Privados Paris',
                    'groups' => 'traslado aeropuerto, van con chofer, minibus grupo, autobus privado',
                    'goal' => 'Utiliser les nouvelles pages ES et eviter les requetes de location sans chauffeur.',
                ],
            ],
            'landing_pages' => [
                ['intent' => 'Airport transfer CDG/ORY/BVA', 'url' => 'https://parisvia.com/paris-airport-transfer/', 'note' => 'Transfert aeroport avec chauffeur, vol suivi, familles et business.'],
                ['intent' => 'Sprinter / minibus 8-22 pax', 'url' => 'https://parisvia.com/mercedes-benz-sprinters/', 'note' => 'Groupes moyens, excursions, business, aeroport avec bagages.'],
                ['intent' => 'Bus / coach 30-50+ pax', 'url' => 'https://parisvia.com/bus-rental-in-paris/', 'note' => 'Seminaires, mariages, groupes tourisme, evenementiel.'],
                ['intent' => 'Luxury van / VIP', 'url' => 'https://parisvia.com/fr/location-van-luxe-paris/', 'note' => 'Classe V, VIP, delegations, service premium avec chauffeur.'],
                ['intent' => 'Devis rapide', 'url' => 'https://parisvia.com/contact-us-paris-via/', 'note' => 'A utiliser pour extensions et sitelinks orientés lead.'],
            ],
            'ad_copy' => [
                [
                    'lang' => 'FR',
                    'ad_group' => 'Van avec chauffeur',
                    'headlines' => ['Van Avec Chauffeur', 'Transfert Prive Paris', 'Devis Rapide 24h'],
                    'descriptions' => ['Mercedes Classe V, Sprinter et minibus avec chauffeurs professionnels a Paris.', 'Transferts aeroport, evenements et groupes. Service ponctuel, confortable, assure.'],
                ],
                [
                    'lang' => 'EN',
                    'ad_group' => 'Bus coach hire',
                    'headlines' => ['Paris Bus With Driver', 'Private Group Transfer', 'Coach Hire Paris'],
                    'descriptions' => ['Chauffeur-driven buses, Sprinters and vans for airports, events and tours in Paris.', 'Fast quote for private group transport. Professional drivers and modern vehicles.'],
                ],
                [
                    'lang' => 'ES',
                    'ad_group' => 'Van con chofer',
                    'headlines' => ['Van Con Chofer Paris', 'Traslado Privado Paris', 'Presupuesto Rapido'],
                    'descriptions' => ['Vehiculos privados con conductor para aeropuertos, eventos y grupos en Paris.', 'Mercedes V-Class, Sprinter y autobus. Servicio profesional, puntual y comodo.'],
                ],
            ],
            'seo' => [
                'Remplacer les expressions self-drive/location sans chauffeur par avec chauffeur sur les pages Sprinter et Bus.',
                'Ajouter des blocs FAQ par intention: aeroport, groupe, evenement, disposition horaire.',
                'Relier chaque annonce a une landing page dediee au vehicule ou au besoin, pas seulement la home.',
                'Ajouter schema LocalBusiness + Service sur les pages principales et harmoniser contact@ / reservation@.',
            ],
        ];
    }

    private function sharedNegativeListStatus(GoogleAdsRestClient $ads): array
    {
        return $this->cachedAdsReport('shared-negative-list', ['start' => 'all', 'end' => 'all'], function () use ($ads) {
            $sharedSet = $this->findSharedNegativeSet($ads);
            if (! $sharedSet) {
                return [
                    'exists' => false,
                    'name' => self::SHARED_NEGATIVE_SET_NAME,
                    'resource_name' => null,
                    'keyword_count' => 0,
                    'attached_campaigns' => [],
                ];
            }

            return [
                'exists' => true,
                'name' => $sharedSet['name'],
                'resource_name' => $sharedSet['resource_name'],
                'keyword_count' => $this->sharedNegativeKeywordCount($ads, $sharedSet['resource_name']),
                'attached_campaigns' => $this->attachedCampaigns($ads, $sharedSet['resource_name']),
            ];
        });
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
            throw new \RuntimeException('La liste partagée n’a pas pu être créée.');
        }

        return [
            'id' => basename($resourceName),
            'name' => self::SHARED_NEGATIVE_SET_NAME,
            'resource_name' => $resourceName,
        ];
    }

    private function findSharedNegativeSet(GoogleAdsRestClient $ads): ?array
    {
        $escapedName = str_replace("'", "\\'", self::SHARED_NEGATIVE_SET_NAME);
        $rows = $ads->searchStream(sprintf(<<<GAQL
SELECT
  shared_set.id,
  shared_set.resource_name,
  shared_set.name,
  shared_set.type
FROM shared_set
WHERE shared_set.name = '%s'
  AND shared_set.type = NEGATIVE_KEYWORDS
LIMIT 1
GAQL, $escapedName));

        $row = $rows[0] ?? null;
        if (! $row) {
            return null;
        }

        return [
            'id' => (string) data_get($row, 'sharedSet.id'),
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
  campaign.name,
  campaign.status,
  campaign.advertising_channel_type
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

    private function attachSharedSetToCampaign(GoogleAdsRestClient $ads, string $sharedSetResourceName, array $campaign): bool
    {
        if ($this->campaignSharedSetExists($ads, $sharedSetResourceName, $campaign['id'])) {
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
        $escapedTerm = str_replace("'", "\\'", $term);
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

    private function sharedNegativeKeywordCount(GoogleAdsRestClient $ads, string $sharedSetResourceName): int
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

    private function attachedCampaigns(GoogleAdsRestClient $ads, string $sharedSetResourceName): array
    {
        $rows = $ads->searchStream(sprintf(<<<GAQL
SELECT
  campaign.id,
  campaign.name,
  campaign_shared_set.status
FROM campaign_shared_set
WHERE campaign_shared_set.shared_set = '%s'
  AND campaign_shared_set.status != REMOVED
ORDER BY campaign.id
GAQL, $sharedSetResourceName));

        return array_map(fn (array $row): array => [
            'id' => (string) data_get($row, 'campaign.id'),
            'name' => data_get($row, 'campaign.name'),
            'status' => data_get($row, 'campaignSharedSet.status'),
        ], $rows);
    }

    private function removeCampaignNegativeIfExists(GoogleAdsRestClient $ads, string $campaignId, string $term, string $matchType): bool
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
}
