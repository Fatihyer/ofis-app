<?php

namespace App\Console\Commands;

use App\Services\GoogleAdsFormLeadConversionUploader;
use Illuminate\Console\Command;
use Throwable;

class GoogleAdsUploadFormLeads extends Command
{
    protected $signature = 'google-ads:upload-form-leads
        {--limit=25 : Maximum pending uploads to process}
        {--lead-id= : Only upload one google_ads_leads.id}
        {--validate-only : Validate uploads without changing Google Ads or local status}';

    protected $description = 'Upload pending Paris Via form leads to Google Ads as offline click conversions';

    public function handle(GoogleAdsFormLeadConversionUploader $uploader): int
    {
        try {
            $summary = $uploader->uploadPending(
                (int) $this->option('limit'),
                $this->option('lead-id') ? (int) $this->option('lead-id') : null,
                (bool) $this->option('validate-only')
            );

            $this->table(
                ['Processed', 'Uploaded', 'Validated', 'Failed', 'Skipped'],
                [[
                    $summary['processed'],
                    $summary['uploaded'],
                    $summary['validated'],
                    $summary['failed'],
                    $summary['skipped'],
                ]]
            );

            return $summary['failed'] > 0 ? self::FAILURE : self::SUCCESS;
        } catch (Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }
    }
}
