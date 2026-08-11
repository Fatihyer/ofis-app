<?php

namespace App\Services;

use Google\Client;
use Google\Service\Sheets;
use Illuminate\Support\Facades\Log;

class GoogleSheetService
{
    private Client $client;
    private ?Sheets $service = null;
    private bool $booted = false;

    public function __construct()
    {
        $this->client = new Client(); // Artık doğru sınıf
    }

    private function boot(): void
    {
        if ($this->booted) return;

        $credentialsPath = env('GOOGLE_CREDENTIALS_PATH', storage_path('app/google/ggolesheet-6727ca4dc3ab.json'));

        if (!file_exists($credentialsPath)) {
            Log::warning('Google credentials missing, GoogleSheetService disabled. Path: ' . $credentialsPath);
            $this->booted = true;
            return;
        }

        $this->client->setApplicationName(config('google.application_name'));
        $this->client->setScopes(config('google.scopes'));
        $this->client->setAuthConfig($credentialsPath);

        // Sheets service'ini burada oluştur
        $this->service = new Sheets($this->client);

        $this->booted = true;
    }

    private function sheets(): Sheets
    {
        $this->boot();

        if (!$this->service) {
            throw new \RuntimeException('Google Sheets not configured on this server (missing credentials).');
        }

        return $this->service;
    }

    public function readSheet(string $spreadsheetId, string $range): array
    {
        try {
            $response = $this->sheets()->spreadsheets_values->get($spreadsheetId, $range);
            return $response->getValues() ?? [];
        } catch (\Exception $e) {
            Log::error('Error reading sheet: ' . $e->getMessage());
            return [];
        }
    }

    public function writeSheet(string $spreadsheetId, string $range, array $values)
    {
        try {
            $body = new \Google\Service\Sheets\ValueRange(['values' => $values]);
            $params = ['valueInputOption' => 'RAW'];
            return $this->sheets()->spreadsheets_values->update($spreadsheetId, $range, $body, $params);
        } catch (\Exception $e) {
            Log::error('Error writing to sheet: ' . $e->getMessage());
            return null;
        }
    }
}
