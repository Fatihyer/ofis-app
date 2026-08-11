<?php

namespace App\Console\Commands;

use App\Services\GoogleAdsRestClient;
use Illuminate\Console\Command;
use Throwable;

class GoogleAdsSetupCampaignStructure extends Command
{
    protected $signature = 'google-ads:setup-campaign-structure
        {--apply : Create the paused campaigns, ad groups, keywords and ads}
        {--daily-budget=15 : Daily budget in EUR for each paused campaign}';

    protected $description = 'Create the paused Paris Via Search campaign structure for FR, EN and ES';

    private const SHARED_NEGATIVE_SET_NAME = 'Paris Via - Shared Negative Keywords';
    private const PARIS_LOCATION = 'geoTargetConstants/1006094';

    public function handle(GoogleAdsRestClient $ads): int
    {
        $apply = (bool) $this->option('apply');
        $dailyBudgetMicros = max(1, (float) $this->option('daily-budget')) * 1000000;

        $this->line($apply ? 'Mode: APPLY' : 'Mode: DRY RUN');
        $this->line('New campaigns will be PAUSED. They will not spend until manually enabled.');
        $this->newLine();

        try {
            $sharedSet = $this->findSharedNegativeSet($ads);
            if (! $sharedSet) {
                throw new \RuntimeException('Shared negative keyword list was not found. Run google-ads:setup-shared-negative-list first.');
            }

            $summary = [
                'campaigns_created' => 0,
                'campaigns_existing' => 0,
                'ad_groups_created' => 0,
                'ad_groups_existing' => 0,
                'keywords_created' => 0,
                'ads_created' => 0,
                'criteria_created' => 0,
                'shared_set_attachments' => 0,
            ];

            foreach ($this->structure() as $campaignPlan) {
                $campaignPlan['budget_micros'] = (int) $dailyBudgetMicros;
                $this->printPlan($campaignPlan);

                if (! $apply) {
                    continue;
                }

                $campaign = $this->findCampaign($ads, $campaignPlan['name']);
                if ($campaign) {
                    $summary['campaigns_existing']++;
                } else {
                    $budgetResource = $this->findOrCreateBudget($ads, $campaignPlan);
                    $campaign = $this->createCampaign($ads, $campaignPlan, $budgetResource);
                    $summary['campaigns_created']++;
                }

                $summary['criteria_created'] += $this->ensureCampaignCriteria($ads, $campaign, $campaignPlan);
                if ($this->ensureSharedSetAttached($ads, $campaign, $sharedSet['resource_name'])) {
                    $summary['shared_set_attachments']++;
                }

                foreach ($campaignPlan['ad_groups'] as $adGroupPlan) {
                    $adGroup = $this->findAdGroup($ads, $campaign['id'], $adGroupPlan['name']);
                    $adGroupWasCreated = false;

                    if ($adGroup) {
                        $summary['ad_groups_existing']++;
                    } else {
                        $adGroup = $this->createAdGroup($ads, $campaign, $adGroupPlan);
                        $summary['ad_groups_created']++;
                        $adGroupWasCreated = true;
                    }

                    $summary['keywords_created'] += $this->ensureKeywords($ads, $adGroup, $adGroupPlan);

                    if ($adGroupWasCreated) {
                        $this->createResponsiveSearchAd($ads, $adGroup, $campaignPlan, $adGroupPlan);
                        $summary['ads_created']++;
                    }
                }
            }

            $this->newLine();
            $this->info($apply ? 'Campaign structure created/updated.' : 'Dry run only. Re-run with --apply to create paused campaigns.');
            $this->table(['Metric', 'Count'], array_map(
                fn (string $key, int $value): array => [$key, $value],
                array_keys($summary),
                $summary
            ));

            return self::SUCCESS;
        } catch (Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }
    }

    private function structure(): array
    {
        return [
            [
                'name' => 'PV Search FR - Chauffeur Groupe Paris',
                'slug' => 'pv_search_fr_chauffeur_groupe_paris',
                'language' => 'languageConstants/1002',
                'ad_groups' => [
                    $this->adGroup('FR - Transfert aeroport', 'aeroport', 'https://parisvia.com/fr/demande-un-devis-2/', [
                        'transfert aeroport paris',
                        'transfert prive paris',
                        'chauffeur aeroport paris',
                        'transfert cdg avec chauffeur',
                        'transfert orly avec chauffeur',
                    ], [
                        'Transfert Aeroport Paris',
                        'Chauffeur Prive Paris',
                        'Van Avec Chauffeur',
                        'Devis Rapide 24h',
                        'Paris Via Chauffeurs',
                    ], [
                        'Vans, Sprinter et bus avec chauffeurs professionnels pour vos trajets a Paris.',
                        'Transferts aeroport, evenements et groupes. Demandez un devis rapide.',
                    ]),
                    $this->adGroup('FR - Van Sprinter chauffeur', 'sprinter', 'https://parisvia.com/fr/location-de-sprinter-paris/', [
                        'location sprinter avec chauffeur',
                        'sprinter chauffeur paris',
                        'van avec chauffeur paris',
                        'minibus avec chauffeur paris',
                        'classe v avec chauffeur',
                    ], [
                        'Sprinter Avec Chauffeur',
                        'Van Avec Chauffeur',
                        'Minibus Groupe Paris',
                        'Mercedes Classe V',
                        'Devis Rapide Paris',
                    ], [
                        'Sprinter, Classe V et minibus avec chauffeurs professionnels a Paris.',
                        'Transport prive pour groupes, entreprises, evenements et aeroports.',
                    ]),
                    $this->adGroup('FR - Bus autocar groupe', 'bus', 'https://parisvia.com/fr/location-bus-paris-2/', [
                        'location bus avec chauffeur paris',
                        'autocar avec chauffeur paris',
                        'bus prive paris',
                        'transport groupe paris',
                        'location minibus avec chauffeur',
                    ], [
                        'Bus Avec Chauffeur',
                        'Autocar Prive Paris',
                        'Transport Groupe Paris',
                        'Devis Bus Rapide',
                        'Paris Via Bus',
                    ], [
                        'Bus, minibus et autocars avec chauffeur pour groupes et evenements a Paris.',
                        'Service professionnel pour seminaires, tours, mariages et transferts aeroport.',
                    ]),
                ],
            ],
            [
                'name' => 'PV Search EN - Private Group Transport Paris',
                'slug' => 'pv_search_en_private_group_transport',
                'language' => 'languageConstants/1000',
                'ad_groups' => [
                    $this->adGroup('EN - Airport transfer', 'airport', 'https://parisvia.com/paris-airport-transfer/', [
                        'private airport transfer paris',
                        'paris airport transfer',
                        'cdg transfer with driver',
                        'orly transfer with driver',
                        'private transfer paris',
                    ], [
                        'Paris Airport Transfer',
                        'Private Transfer Paris',
                        'Van With Driver',
                        'Fast Group Quote',
                        'Paris Via Chauffeurs',
                    ], [
                        'Chauffeur-driven vans and buses for airport transfers, events and tours in Paris.',
                        'Private group transport with professional drivers and modern vehicles.',
                    ]),
                    $this->adGroup('EN - Van Sprinter driver', 'sprinter', 'https://parisvia.com/sprinter-rental-in-paris/', [
                        'van with driver paris',
                        'sprinter van with driver paris',
                        'minibus with driver paris',
                        'mercedes v class chauffeur paris',
                        'private van paris',
                    ], [
                        'Paris Van With Driver',
                        'Sprinter With Driver',
                        'Private Van Paris',
                        'Minibus With Driver',
                        'Fast Group Quote',
                    ], [
                        'Mercedes vans, Sprinters and minibuses with professional drivers in Paris.',
                        'Ideal for airport transfers, events, business travel and private tours.',
                    ]),
                    $this->adGroup('EN - Bus coach hire', 'bus', 'https://parisvia.com/bus-rental-in-paris/', [
                        'bus hire paris with driver',
                        'coach hire paris',
                        'private bus paris',
                        'group transport paris',
                        'charter bus paris',
                    ], [
                        'Coach Hire Paris',
                        'Bus With Driver Paris',
                        'Private Group Transport',
                        'Paris Bus Quote',
                        'Modern Buses Paris',
                    ], [
                        'Private buses and coaches with drivers for groups, events and airport transfers.',
                        'Fast quote for professional group transport in Paris and nearby areas.',
                    ]),
                ],
            ],
            [
                'name' => 'PV Search ES - Traslados Privados Paris',
                'slug' => 'pv_search_es_traslados_privados',
                'language' => 'languageConstants/1003',
                'ad_groups' => [
                    $this->adGroup('ES - Traslado aeropuerto', 'aeropuerto', 'https://parisvia.com/es/traslado-vip-aeropuerto-paris/', [
                        'traslado aeropuerto paris',
                        'traslado privado paris',
                        'chofer aeropuerto paris',
                        'traslado cdg con chofer',
                        'traslado orly con chofer',
                    ], [
                        'Traslado Aeropuerto',
                        'Chofer Privado Paris',
                        'Van Con Chofer Paris',
                        'Presupuesto Rapido',
                        'Paris Via Choferes',
                    ], [
                        'Vehiculos con chofer para aeropuertos, eventos y grupos en Paris.',
                        'Servicio privado con vans, Sprinter y autobus. Solicite presupuesto.',
                    ]),
                    $this->adGroup('ES - Van Sprinter chofer', 'sprinter', 'https://parisvia.com/es/alquiler-sprinter-paris/', [
                        'van con chofer paris',
                        'sprinter con chofer paris',
                        'minibus con conductor paris',
                        'mercedes clase v con chofer',
                        'van privada paris',
                    ], [
                        'Sprinter Con Chofer',
                        'Van Con Chofer Paris',
                        'Minibus Privado Paris',
                        'Mercedes Clase V',
                        'Presupuesto Rapido',
                    ], [
                        'Sprinter, vans y minibuses con conductor profesional para grupos en Paris.',
                        'Traslados privados para aeropuertos, empresas, eventos y tours.',
                    ]),
                    $this->adGroup('ES - Autobus grupo', 'autobus', 'https://parisvia.com/es/alquiler-autobus-paris/', [
                        'alquiler autobus con conductor paris',
                        'autobus privado paris',
                        'bus con chofer paris',
                        'transporte grupos paris',
                        'alquiler minibus con conductor',
                    ], [
                        'Autobus Privado Paris',
                        'Bus Con Chofer Paris',
                        'Transporte Grupos Paris',
                        'Presupuesto Autobus',
                        'Paris Via Autobus',
                    ], [
                        'Autobuses, minibuses y vans con conductor para grupos y eventos en Paris.',
                        'Servicio privado para tours, congresos, bodas y traslados de aeropuerto.',
                    ]),
                ],
            ],
        ];
    }

    private function adGroup(string $name, string $slug, string $url, array $keywords, array $headlines, array $descriptions): array
    {
        return compact('name', 'slug', 'url', 'keywords', 'headlines', 'descriptions');
    }

    private function printPlan(array $campaignPlan): void
    {
        $this->info($campaignPlan['name']);
        $this->line(sprintf(
            '- status: PAUSED, location: Paris FR, language: %s, budget: %.2f EUR/day',
            basename($campaignPlan['language']),
            $campaignPlan['budget_micros'] / 1000000
        ));

        foreach ($campaignPlan['ad_groups'] as $adGroup) {
            $this->line(sprintf(
                '  - %s: %d keyword intent(s), landing %s',
                $adGroup['name'],
                count($adGroup['keywords']),
                $adGroup['url']
            ));
        }
    }

    private function findOrCreateBudget(GoogleAdsRestClient $ads, array $campaignPlan): string
    {
        $budgetName = $campaignPlan['name'] . ' Budget';
        $rows = $ads->searchStream(sprintf(<<<GAQL
SELECT
  campaign_budget.resource_name,
  campaign_budget.name
FROM campaign_budget
WHERE campaign_budget.name = '%s'
LIMIT 1
GAQL, $this->gaqlString($budgetName)));

        $existing = (string) data_get($rows, '0.campaignBudget.resourceName');
        if ($existing !== '') {
            return $existing;
        }

        $response = $ads->mutate('campaignBudgets', [[
            'create' => [
                'name' => $budgetName,
                'amountMicros' => $campaignPlan['budget_micros'],
                'deliveryMethod' => 'STANDARD',
                'explicitlyShared' => false,
            ],
        ]], [
            'responseContentType' => 'RESOURCE_NAME_ONLY',
        ]);

        $resourceName = (string) data_get($response, 'results.0.resourceName');
        if ($resourceName === '') {
            throw new \RuntimeException('Campaign budget could not be created for ' . $campaignPlan['name']);
        }

        return $resourceName;
    }

    private function createCampaign(GoogleAdsRestClient $ads, array $campaignPlan, string $budgetResource): array
    {
        $response = $ads->mutate('campaigns', [[
            'create' => [
                'name' => $campaignPlan['name'],
                'status' => 'PAUSED',
                'advertisingChannelType' => 'SEARCH',
                'containsEuPoliticalAdvertising' => 'DOES_NOT_CONTAIN_EU_POLITICAL_ADVERTISING',
                'campaignBudget' => $budgetResource,
                'manualCpc' => [
                    'enhancedCpcEnabled' => false,
                ],
                'networkSettings' => [
                    'targetGoogleSearch' => true,
                    'targetSearchNetwork' => true,
                    'targetContentNetwork' => false,
                    'targetPartnerSearchNetwork' => false,
                ],
                'geoTargetTypeSetting' => [
                    'positiveGeoTargetType' => 'PRESENCE_OR_INTEREST',
                    'negativeGeoTargetType' => 'PRESENCE',
                ],
            ],
        ]], [
            'responseContentType' => 'RESOURCE_NAME_ONLY',
        ]);

        $resourceName = (string) data_get($response, 'results.0.resourceName');
        if ($resourceName === '') {
            throw new \RuntimeException('Campaign could not be created: ' . $campaignPlan['name']);
        }

        return [
            'id' => basename($resourceName),
            'name' => $campaignPlan['name'],
            'resource_name' => $resourceName,
        ];
    }

    private function findCampaign(GoogleAdsRestClient $ads, string $name): ?array
    {
        $rows = $ads->searchStream(sprintf(<<<GAQL
SELECT
  campaign.id,
  campaign.resource_name,
  campaign.name,
  campaign.status
FROM campaign
WHERE campaign.name = '%s'
LIMIT 1
GAQL, $this->gaqlString($name)));

        $resourceName = (string) data_get($rows, '0.campaign.resourceName');
        if ($resourceName === '') {
            return null;
        }

        return [
            'id' => (string) data_get($rows, '0.campaign.id'),
            'name' => (string) data_get($rows, '0.campaign.name'),
            'resource_name' => $resourceName,
        ];
    }

    private function ensureCampaignCriteria(GoogleAdsRestClient $ads, array $campaign, array $campaignPlan): int
    {
        $existing = $this->campaignCriteria($ads, $campaign['id']);
        $operations = [];

        if (! isset($existing['location'][self::PARIS_LOCATION])) {
            $operations[] = [
                'create' => [
                    'campaign' => $campaign['resource_name'],
                    'location' => [
                        'geoTargetConstant' => self::PARIS_LOCATION,
                    ],
                ],
            ];
        }

        if (! isset($existing['language'][$campaignPlan['language']])) {
            $operations[] = [
                'create' => [
                    'campaign' => $campaign['resource_name'],
                    'language' => [
                        'languageConstant' => $campaignPlan['language'],
                    ],
                ],
            ];
        }

        if ($operations === []) {
            return 0;
        }

        $this->mutateWithPartialFailureCheck($ads, 'campaignCriteria', $operations);

        return count($operations);
    }

    private function campaignCriteria(GoogleAdsRestClient $ads, string $campaignId): array
    {
        $rows = $ads->searchStream(sprintf(<<<GAQL
SELECT
  campaign_criterion.type,
  campaign_criterion.location.geo_target_constant,
  campaign_criterion.language.language_constant,
  campaign_criterion.negative
FROM campaign_criterion
WHERE campaign.id = %s
  AND campaign_criterion.type IN (LOCATION, LANGUAGE)
  AND campaign_criterion.status != REMOVED
GAQL, preg_replace('/\D+/', '', $campaignId)));

        $criteria = [
            'location' => [],
            'language' => [],
        ];

        foreach ($rows as $row) {
            if ((bool) data_get($row, 'campaignCriterion.negative')) {
                continue;
            }

            $location = (string) data_get($row, 'campaignCriterion.location.geoTargetConstant');
            if ($location !== '') {
                $criteria['location'][$location] = true;
            }

            $language = (string) data_get($row, 'campaignCriterion.language.languageConstant');
            if ($language !== '') {
                $criteria['language'][$language] = true;
            }
        }

        return $criteria;
    }

    private function ensureSharedSetAttached(GoogleAdsRestClient $ads, array $campaign, string $sharedSetResourceName): bool
    {
        $rows = $ads->searchStream(sprintf(<<<GAQL
SELECT
  campaign_shared_set.resource_name
FROM campaign_shared_set
WHERE campaign.id = %s
  AND campaign_shared_set.shared_set = '%s'
  AND campaign_shared_set.status != REMOVED
LIMIT 1
GAQL, preg_replace('/\D+/', '', $campaign['id']), $sharedSetResourceName));

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

    private function createAdGroup(GoogleAdsRestClient $ads, array $campaign, array $adGroupPlan): array
    {
        $response = $ads->mutate('adGroups', [[
            'create' => [
                'campaign' => $campaign['resource_name'],
                'name' => $adGroupPlan['name'],
                'status' => 'ENABLED',
                'type' => 'SEARCH_STANDARD',
                'cpcBidMicros' => 1200000,
            ],
        ]], [
            'responseContentType' => 'RESOURCE_NAME_ONLY',
        ]);

        $resourceName = (string) data_get($response, 'results.0.resourceName');
        if ($resourceName === '') {
            throw new \RuntimeException('Ad group could not be created: ' . $adGroupPlan['name']);
        }

        return [
            'id' => basename($resourceName),
            'name' => $adGroupPlan['name'],
            'resource_name' => $resourceName,
        ];
    }

    private function findAdGroup(GoogleAdsRestClient $ads, string $campaignId, string $name): ?array
    {
        $rows = $ads->searchStream(sprintf(<<<GAQL
SELECT
  ad_group.id,
  ad_group.resource_name,
  ad_group.name,
  ad_group.status
FROM ad_group
WHERE campaign.id = %s
  AND ad_group.name = '%s'
  AND ad_group.status != REMOVED
LIMIT 1
GAQL, preg_replace('/\D+/', '', $campaignId), $this->gaqlString($name)));

        $resourceName = (string) data_get($rows, '0.adGroup.resourceName');
        if ($resourceName === '') {
            return null;
        }

        return [
            'id' => (string) data_get($rows, '0.adGroup.id'),
            'name' => (string) data_get($rows, '0.adGroup.name'),
            'resource_name' => $resourceName,
        ];
    }

    private function ensureKeywords(GoogleAdsRestClient $ads, array $adGroup, array $adGroupPlan): int
    {
        $existing = $this->existingKeywords($ads, $adGroup['id']);
        $operations = [];

        foreach ($adGroupPlan['keywords'] as $keyword) {
            foreach (['PHRASE', 'EXACT'] as $matchType) {
                $key = $this->keywordKey($keyword, $matchType);
                if (isset($existing[$key])) {
                    continue;
                }

                $operations[] = [
                    'create' => [
                        'adGroup' => $adGroup['resource_name'],
                        'status' => 'ENABLED',
                        'keyword' => [
                            'text' => $keyword,
                            'matchType' => $matchType,
                        ],
                    ],
                ];
            }
        }

        if ($operations === []) {
            return 0;
        }

        foreach (array_chunk($operations, 100) as $chunk) {
            $this->mutateWithPartialFailureCheck($ads, 'adGroupCriteria', $chunk);
        }

        return count($operations);
    }

    private function existingKeywords(GoogleAdsRestClient $ads, string $adGroupId): array
    {
        $rows = $ads->searchStream(sprintf(<<<GAQL
SELECT
  ad_group_criterion.keyword.text,
  ad_group_criterion.keyword.match_type
FROM ad_group_criterion
WHERE ad_group.id = %s
  AND ad_group_criterion.type = KEYWORD
  AND ad_group_criterion.status != REMOVED
GAQL, preg_replace('/\D+/', '', $adGroupId)));

        $existing = [];
        foreach ($rows as $row) {
            $existing[$this->keywordKey(
                (string) data_get($row, 'adGroupCriterion.keyword.text'),
                (string) data_get($row, 'adGroupCriterion.keyword.matchType')
            )] = true;
        }

        return $existing;
    }

    private function createResponsiveSearchAd(GoogleAdsRestClient $ads, array $adGroup, array $campaignPlan, array $adGroupPlan): void
    {
        $finalUrl = $this->finalUrl($adGroupPlan['url'], $campaignPlan['slug'], $adGroupPlan['slug']);

        $ads->mutate('adGroupAds', [[
            'create' => [
                'adGroup' => $adGroup['resource_name'],
                'status' => 'ENABLED',
                'ad' => [
                    'finalUrls' => [$finalUrl],
                    'responsiveSearchAd' => [
                        'headlines' => array_map(fn (string $text): array => ['text' => $text], $adGroupPlan['headlines']),
                        'descriptions' => array_map(fn (string $text): array => ['text' => $text], $adGroupPlan['descriptions']),
                    ],
                ],
            ],
        ]], [
            'responseContentType' => 'RESOURCE_NAME_ONLY',
        ]);
    }

    private function findSharedNegativeSet(GoogleAdsRestClient $ads): ?array
    {
        $rows = $ads->searchStream(sprintf(<<<GAQL
SELECT
  shared_set.resource_name,
  shared_set.name,
  shared_set.type
FROM shared_set
WHERE shared_set.name = '%s'
  AND shared_set.type = NEGATIVE_KEYWORDS
LIMIT 1
GAQL, $this->gaqlString(self::SHARED_NEGATIVE_SET_NAME)));

        $resourceName = (string) data_get($rows, '0.sharedSet.resourceName');
        if ($resourceName === '') {
            return null;
        }

        return [
            'name' => (string) data_get($rows, '0.sharedSet.name'),
            'resource_name' => $resourceName,
        ];
    }

    private function mutateWithPartialFailureCheck(GoogleAdsRestClient $ads, string $resource, array $operations): void
    {
        $response = $ads->mutate($resource, $operations, [
            'partialFailure' => true,
            'responseContentType' => 'RESOURCE_NAME_ONLY',
        ]);

        if (! empty($response['partialFailureError'])) {
            throw new \RuntimeException($resource . ' partial failure: ' . json_encode($response['partialFailureError'], JSON_UNESCAPED_UNICODE));
        }
    }

    private function finalUrl(string $url, string $campaignSlug, string $adGroupSlug): string
    {
        $separator = str_contains($url, '?') ? '&' : '?';

        return $url . $separator . http_build_query([
            'utm_source' => 'google',
            'utm_medium' => 'cpc',
            'utm_campaign' => $campaignSlug,
            'utm_content' => $adGroupSlug,
        ]) . '&utm_term={keyword}';
    }

    private function keywordKey(string $keyword, string $matchType): string
    {
        return $this->normalize($keyword) . '|' . strtoupper($matchType);
    }

    private function normalize(string $value): string
    {
        $value = strtolower($value);
        $ascii = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
        $value = $ascii !== false ? $ascii : $value;
        $value = preg_replace('/[^a-z0-9]+/', ' ', $value) ?: '';

        return trim($value);
    }

    private function gaqlString(string $value): string
    {
        return str_replace(['\\', "'"], ['\\\\', "\\'"], $value);
    }
}
