<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class GoogleAdsFormLeadConversionUploader
{
    public function __construct(private GoogleAdsRestClient $ads)
    {
    }

    public function uploadPending(int $limit = 25, ?int $leadId = null, bool $validateOnly = false): array
    {
        $query = DB::table('google_ads_conversion_uploads as u')
            ->join('google_ads_leads as l', 'l.id', '=', 'u.google_ads_lead_id')
            ->select('u.*', 'l.gclid', 'l.gbraid', 'l.wbraid', 'l.created_at as lead_created_at')
            ->where('u.conversion_action', 'lead_submit')
            ->whereIn('u.upload_status', ['pending', 'failed'])
            ->where(function ($query) {
                $query->whereNull('u.last_attempt_at')
                    ->orWhere('u.last_attempt_at', '<=', now('Europe/Paris')->subMinutes(30));
            })
            ->orderBy('u.id')
            ->limit(max(1, min(100, $limit)));

        if ($leadId) {
            $query->where('u.google_ads_lead_id', $leadId);
        }

        $rows = $query->get();
        $summary = [
            'processed' => 0,
            'uploaded' => 0,
            'failed' => 0,
            'skipped' => 0,
            'validated' => 0,
        ];

        foreach ($rows as $row) {
            $summary['processed']++;

            try {
                $conversion = $this->buildConversion($row);

                if (! $conversion) {
                    $summary['skipped']++;
                    $this->markSkipped($row, $validateOnly, 'No gclid, gbraid or wbraid on this lead.');
                    continue;
                }

                $response = $this->ads->uploadClickConversions([$conversion], [
                    'validateOnly' => $validateOnly,
                    'partialFailure' => true,
                ]);

                $partialFailure = $response['partialFailureError'] ?? null;
                if ($partialFailure) {
                    throw new RuntimeException(json_encode($partialFailure, JSON_UNESCAPED_UNICODE));
                }

                if ($validateOnly) {
                    $summary['validated']++;
                } else {
                    $summary['uploaded']++;
                    $this->markUploaded($row, $response);
                }
            } catch (\Throwable $e) {
                $summary['failed']++;
                $this->markFailed($row, $validateOnly, $e->getMessage());

                Log::warning('Google Ads form lead conversion upload failed.', [
                    'google_ads_lead_id' => $row->google_ads_lead_id,
                    'upload_id' => $row->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $summary;
    }

    public function uploadLead(int $leadId, bool $validateOnly = false): array
    {
        return $this->uploadPending(1, $leadId, $validateOnly);
    }

    public function conversionActionResourceName(): string
    {
        $configured = trim((string) config('services.google_ads.form_lead_conversion_action'));
        if ($configured !== '') {
            return $configured;
        }

        $name = $this->conversionActionName();
        $cacheKey = 'google_ads_form_lead_conversion_action_' . md5($name);

        return Cache::remember($cacheKey, now()->addMinutes(15), function () use ($name): string {
            $escapedName = str_replace("'", "\\'", $name);
            $rows = $this->ads->searchStream(sprintf(<<<GAQL
SELECT
  conversion_action.resource_name,
  conversion_action.name,
  conversion_action.type,
  conversion_action.status
FROM conversion_action
WHERE conversion_action.name = '%s'
  AND conversion_action.status != REMOVED
LIMIT 1
GAQL, $escapedName));

            $resourceName = (string) data_get($rows, '0.conversionAction.resourceName');
            if ($resourceName === '') {
                throw new RuntimeException(sprintf(
                    'Google Ads conversion action "%s" was not found. Run php artisan google-ads:setup-form-lead-conversion --apply first.',
                    $name
                ));
            }

            return $resourceName;
        });
    }

    public function conversionActionName(): string
    {
        $name = trim((string) config('services.google_ads.form_lead_conversion_name'));

        return $name !== '' ? $name : 'Paris Via - Form Lead';
    }

    private function buildConversion(object $row): ?array
    {
        $clickId = $this->clickId($row);
        if (! $clickId) {
            return null;
        }

        $conversionTime = $this->conversionTime($row);
        $conversion = [
            'conversionAction' => $this->conversionActionResourceName(),
            'conversionDateTime' => $conversionTime,
            'conversionValue' => (float) ($row->conversion_value ?? config('services.google_ads.form_lead_conversion_value', 1)),
            'currencyCode' => $row->currency ?: 'EUR',
            'orderId' => 'parisvia-form-lead-' . $row->google_ads_lead_id,
            'conversionEnvironment' => 'WEB',
        ];

        $conversion[$clickId['field']] = $clickId['value'];

        return $conversion;
    }

    private function clickId(object $row): ?array
    {
        foreach (['gclid' => 'gclid', 'wbraid' => 'wbraid', 'gbraid' => 'gbraid'] as $column => $field) {
            $value = trim((string) ($row->{$column} ?? ''));
            if ($value !== '') {
                return [
                    'field' => $field,
                    'value' => $value,
                ];
            }
        }

        return null;
    }

    private function conversionTime(object $row): string
    {
        $time = $row->conversion_time ?: $row->lead_created_at ?: now('Europe/Paris');

        return Carbon::parse($time, 'Europe/Paris')
            ->timezone('Europe/Paris')
            ->format('Y-m-d H:i:sP');
    }

    private function markUploaded(object $row, array $response): void
    {
        DB::table('google_ads_conversion_uploads')
            ->where('id', $row->id)
            ->update([
                'upload_status' => 'uploaded',
                'upload_attempts' => DB::raw('upload_attempts + 1'),
                'last_attempt_at' => now('Europe/Paris'),
                'uploaded_at' => now('Europe/Paris'),
                'google_response' => json_encode($response, JSON_UNESCAPED_UNICODE),
                'error_message' => null,
                'updated_at' => now('Europe/Paris'),
            ]);

        DB::table('google_ads_leads')
            ->where('id', $row->google_ads_lead_id)
            ->update([
                'sync_status' => 'uploaded',
                'sync_error' => null,
                'updated_at' => now('Europe/Paris'),
            ]);
    }

    private function markFailed(object $row, bool $validateOnly, string $message): void
    {
        if ($validateOnly) {
            return;
        }

        DB::table('google_ads_conversion_uploads')
            ->where('id', $row->id)
            ->update([
                'upload_status' => 'failed',
                'upload_attempts' => DB::raw('upload_attempts + 1'),
                'last_attempt_at' => now('Europe/Paris'),
                'error_message' => substr($message, 0, 4000),
                'updated_at' => now('Europe/Paris'),
            ]);

        DB::table('google_ads_leads')
            ->where('id', $row->google_ads_lead_id)
            ->update([
                'sync_status' => 'upload_failed',
                'sync_error' => substr($message, 0, 4000),
                'updated_at' => now('Europe/Paris'),
            ]);
    }

    private function markSkipped(object $row, bool $validateOnly, string $message): void
    {
        if ($validateOnly) {
            return;
        }

        DB::table('google_ads_conversion_uploads')
            ->where('id', $row->id)
            ->update([
                'upload_status' => 'skipped',
                'upload_attempts' => DB::raw('upload_attempts + 1'),
                'last_attempt_at' => now('Europe/Paris'),
                'error_message' => $message,
                'updated_at' => now('Europe/Paris'),
            ]);

        DB::table('google_ads_leads')
            ->where('id', $row->google_ads_lead_id)
            ->update([
                'sync_status' => 'skipped_no_click_id',
                'sync_error' => $message,
                'updated_at' => now('Europe/Paris'),
            ]);
    }
}
