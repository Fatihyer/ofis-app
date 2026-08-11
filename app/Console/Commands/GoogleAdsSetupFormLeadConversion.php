<?php

namespace App\Console\Commands;

use App\Services\GoogleAdsFormLeadConversionUploader;
use App\Services\GoogleAdsRestClient;
use Illuminate\Console\Command;
use Throwable;

class GoogleAdsSetupFormLeadConversion extends Command
{
    protected $signature = 'google-ads:setup-form-lead-conversion
        {--apply : Create the conversion action. Without this option Google Ads only validates it}';

    protected $description = 'Create or validate the Google Ads offline form lead conversion action';

    public function handle(GoogleAdsRestClient $ads, GoogleAdsFormLeadConversionUploader $uploader): int
    {
        $name = $uploader->conversionActionName();
        $apply = (bool) $this->option('apply');

        try {
            $existing = $this->findExisting($ads, $name);
            if ($existing) {
                $this->info('Conversion action already exists.');
                $this->line('Name: ' . data_get($existing, 'conversionAction.name'));
                $this->line('Resource: ' . data_get($existing, 'conversionAction.resourceName'));
                $this->line('Type: ' . data_get($existing, 'conversionAction.type'));
                $this->line('Primary: ' . (data_get($existing, 'conversionAction.primaryForGoal') ? 'YES' : 'NO'));

                return self::SUCCESS;
            }

            $operation = [
                'create' => [
                    'name' => $name,
                    'type' => 'UPLOAD_CLICKS',
                    'category' => 'SUBMIT_LEAD_FORM',
                    'status' => 'ENABLED',
                    'primaryForGoal' => true,
                    'countingType' => 'ONE_PER_CLICK',
                    'clickThroughLookbackWindowDays' => 90,
                    'valueSettings' => [
                        'defaultValue' => (float) config('services.google_ads.form_lead_conversion_value', 1),
                        'defaultCurrencyCode' => 'EUR',
                        'alwaysUseDefaultValue' => true,
                    ],
                ],
            ];

            $response = $ads->mutate('conversionActions', [$operation], [
                'validateOnly' => ! $apply,
                'responseContentType' => 'RESOURCE_NAME_ONLY',
            ]);

            if ($apply) {
                $this->info('Conversion action created.');
                $this->line('Resource: ' . data_get($response, 'results.0.resourceName'));
            } else {
                $this->info('Validation passed. Re-run with --apply to create it.');
            }

            return self::SUCCESS;
        } catch (Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }
    }

    private function findExisting(GoogleAdsRestClient $ads, string $name): ?array
    {
        $escapedName = str_replace("'", "\\'", $name);
        $rows = $ads->searchStream(sprintf(<<<GAQL
SELECT
  conversion_action.resource_name,
  conversion_action.name,
  conversion_action.type,
  conversion_action.status,
  conversion_action.primary_for_goal
FROM conversion_action
WHERE conversion_action.name = '%s'
  AND conversion_action.status != REMOVED
LIMIT 1
GAQL, $escapedName));

        return $rows[0] ?? null;
    }
}
