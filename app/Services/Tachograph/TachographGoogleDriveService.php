<?php

namespace App\Services\Tachograph;

use Google\Client;
use Google\Service\Drive;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class TachographGoogleDriveService
{
    private ?Drive $service = null;

    public function isConfigured(): bool
    {
        return is_file($this->credentialsPath()) || $this->hasOAuthToken();
    }

    public function connectionMode(): string
    {
        if (is_file($this->credentialsPath())) {
            return 'service_account';
        }

        if ($this->hasOAuthToken()) {
            return 'oauth';
        }

        if ($this->hasOAuthCredentials()) {
            return 'oauth_not_connected';
        }

        return 'missing_credentials';
    }

    public function hasOAuthCredentials(): bool
    {
        return (bool) ($this->setting('google_drive_client_id') ?: env('GOOGLE_DRIVE_CLIENT_ID'))
            && (bool) ($this->setting('google_drive_client_secret') ?: env('GOOGLE_DRIVE_CLIENT_SECRET'));
    }

    public function authUrl(string $redirectUri): string
    {
        $client = $this->oauthClient($redirectUri);
        $client->setPrompt('consent');

        return $client->createAuthUrl();
    }

    public function storeTokenFromCode(string $code, string $redirectUri): array
    {
        $client = $this->oauthClient($redirectUri);
        $token = $client->fetchAccessTokenWithAuthCode($code);

        if (!empty($token['error'])) {
            throw new \RuntimeException($token['error_description'] ?? $token['error']);
        }

        if (empty($token['refresh_token'])) {
            $oldToken = $this->oauthToken();
            if (!empty($oldToken['refresh_token'])) {
                $token['refresh_token'] = $oldToken['refresh_token'];
            }
        }

        $this->setSetting('google_drive_oauth_token', json_encode($token));
        $this->setSetting('google_drive_connected_at', now()->format('Y-m-d H:i:s'));

        return $token;
    }

    public function listC1BFiles(string $folderId, int $limit = 25): array
    {
        if (!$this->isConfigured() || !$folderId) {
            return [];
        }

        $query = sprintf(
            "'%s' in parents and trashed = false and name contains '.C1B'",
            str_replace("'", "\\'", $folderId)
        );

        $response = $this->drive()->files->listFiles([
            'q' => $query,
            'pageSize' => $limit,
            'orderBy' => 'modifiedTime desc',
            'fields' => 'files(id,name,mimeType,md5Checksum,size,modifiedTime)',
            'supportsAllDrives' => true,
            'includeItemsFromAllDrives' => true,
        ]);

        return $response->getFiles() ?: [];
    }

    public function downloadFileContent(string $fileId): string
    {
        $response = $this->drive()->files->get($fileId, [
            'alt' => 'media',
            'supportsAllDrives' => true,
        ]);

        return (string) $response->getBody()->getContents();
    }

    private function drive(): Drive
    {
        if ($this->service) {
            return $this->service;
        }

        $credentialsPath = $this->credentialsPath();
        if (!is_file($credentialsPath)) {
            $client = $this->oauthAuthorizedClient();
            $this->service = new Drive($client);

            return $this->service;
        }

        $client = new Client();
        $client->setApplicationName(config('google.application_name', 'Paris Via'));
        $client->setAuthConfig($credentialsPath);
        $client->setScopes([Drive::DRIVE_READONLY]);

        $this->service = new Drive($client);

        return $this->service;
    }

    private function oauthAuthorizedClient(): Client
    {
        $client = $this->oauthClient(route('tachograph.drive.callback'));
        $token = $this->oauthToken();

        if (!$token) {
            throw new \RuntimeException('Google Drive OAuth token missing.');
        }

        $client->setAccessToken($token);

        if ($client->isAccessTokenExpired()) {
            if (empty($token['refresh_token'])) {
                throw new \RuntimeException('Google Drive refresh token missing, reconnect Google account.');
            }

            $token = $client->fetchAccessTokenWithRefreshToken($token['refresh_token']);
            if (!empty($token['error'])) {
                throw new \RuntimeException($token['error_description'] ?? $token['error']);
            }
            $this->setSetting('google_drive_oauth_token', json_encode($client->getAccessToken()));
        }

        return $client;
    }

    private function oauthClient(string $redirectUri): Client
    {
        $clientId = $this->setting('google_drive_client_id') ?: env('GOOGLE_DRIVE_CLIENT_ID');
        $clientSecret = $this->setting('google_drive_client_secret') ?: env('GOOGLE_DRIVE_CLIENT_SECRET');

        if (!$clientId || !$clientSecret) {
            throw new \RuntimeException('Google Drive OAuth client ID/secret missing.');
        }

        $client = new Client();
        $client->setApplicationName(config('google.application_name', 'Paris Via'));
        $client->setClientId($clientId);
        $client->setClientSecret($clientSecret);
        $client->setRedirectUri($redirectUri);
        $client->setAccessType('offline');
        $client->setIncludeGrantedScopes(true);
        $client->setScopes([Drive::DRIVE_READONLY]);

        return $client;
    }

    private function hasOAuthToken(): bool
    {
        return $this->hasOAuthCredentials() && (bool) $this->oauthToken();
    }

    private function oauthToken(): ?array
    {
        $raw = $this->setting('google_drive_oauth_token');
        if (!$raw) {
            return null;
        }

        $token = json_decode($raw, true);

        return is_array($token) ? $token : null;
    }

    private function credentialsPath(): string
    {
        $configured = config('google.credentials_path');
        if ($configured) {
            return $configured;
        }

        return env('GOOGLE_CREDENTIALS_PATH', storage_path('app/google/ggolesheet-6727ca4dc3ab.json'));
    }

    private function setting(string $key, $default = null)
    {
        if (!Schema::hasTable('tachograph_settings')) {
            return $default;
        }

        $value = DB::table('tachograph_settings')->where('key', $key)->value('value');

        return $value === null ? $default : $value;
    }

    private function setSetting(string $key, $value): void
    {
        if (!Schema::hasTable('tachograph_settings')) {
            return;
        }

        DB::table('tachograph_settings')->updateOrInsert(
            ['key' => $key],
            ['value' => $value, 'updated_at' => now(), 'created_at' => now()]
        );
    }
}
