<?php

namespace App\Domain\Platform\Services\SEO;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

/**
 * Google Indexing API Service
 * يُبلغ Google بالصفحات الجديدة/المحدثة/المحذوفة
 *
 * Requirements:
 * 1. Enable Indexing API in Google Cloud Console
 * 2. Create Service Account with Indexing API access
 * 3. Download JSON credentials and store path in .env as GOOGLE_INDEXING_CREDENTIALS
 * 4. Add service account email to Search Console as owner
 */
class IndexingApiService
{
    protected string $endpoint = 'https://indexing.googleapis.com/v3/urlNotifications:publish';
    protected ?string $accessToken = null;

    /**
     * Notify Google about a new or updated URL
     */
    public function notifyUrlUpdated(string $url): array
    {
        return $this->notify($url, 'URL_UPDATED');
    }

    /**
     * Notify Google about a deleted URL
     */
    public function notifyUrlDeleted(string $url): array
    {
        return $this->notify($url, 'URL_DELETED');
    }

    /**
     * Batch notify multiple URLs
     */
    public function batchNotify(array $urls, string $type = 'URL_UPDATED'): array
    {
        $results = [];
        foreach ($urls as $url) {
            $results[$url] = $this->notify($url, $type);

            // Rate limiting - Google allows 200 requests per minute
            usleep(350000); // 0.35 seconds between requests
        }
        return $results;
    }

    /**
     * Send notification to Google
     */
    protected function notify(string $url, string $type): array
    {
        try {
            $token = $this->getAccessToken();

            if (!$token) {
                return [
                    'success' => false,
                    'error' => 'Could not obtain access token',
                    'url' => $url
                ];
            }

            $response = Http::withToken($token)
                ->timeout(30)
                ->post($this->endpoint, [
                    'url' => $url,
                    'type' => $type
                ]);

            if ($response->successful()) {
                Log::info("Indexing API: {$type} notification sent for {$url}");
                return [
                    'success' => true,
                    'url' => $url,
                    'type' => $type,
                    'response' => $response->json()
                ];
            }

            Log::warning("Indexing API failed for {$url}", [
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            return [
                'success' => false,
                'url' => $url,
                'error' => $response->body(),
                'status' => $response->status()
            ];

        } catch (\Exception $e) {
            Log::error("Indexing API exception for {$url}: " . $e->getMessage());
            return [
                'success' => false,
                'url' => $url,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get OAuth2 access token using service account
     */
    protected function getAccessToken(): ?string
    {
        // Check cache first
        $cached = Cache::get('google_indexing_token');
        if ($cached) {
            return $cached;
        }

        $credentialsPath = config('services.google.indexing_credentials')
            ?? env('GOOGLE_INDEXING_CREDENTIALS');

        if (!$credentialsPath || !file_exists($credentialsPath)) {
            Log::warning('Google Indexing API credentials not found');
            return null;
        }

        try {
            $credentials = json_decode(file_get_contents($credentialsPath), true);

            // Create JWT
            $header = base64_encode(json_encode([
                'alg' => 'RS256',
                'typ' => 'JWT'
            ]));

            $now = time();
            $claims = base64_encode(json_encode([
                'iss' => $credentials['client_email'],
                'scope' => 'https://www.googleapis.com/auth/indexing',
                'aud' => 'https://oauth2.googleapis.com/token',
                'exp' => $now + 3600,
                'iat' => $now
            ]));

            $signature = '';
            openssl_sign(
                $header . '.' . $claims,
                $signature,
                $credentials['private_key'],
                'SHA256'
            );
            $signature = base64_encode($signature);

            $jwt = $header . '.' . $claims . '.' . $signature;

            // Exchange JWT for access token
            $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion' => $jwt
            ]);

            if ($response->successful()) {
                $token = $response->json('access_token');
                $expiresIn = $response->json('expires_in', 3600) - 60;

                Cache::put('google_indexing_token', $token, $expiresIn);

                return $token;
            }

            Log::error('Failed to get Indexing API token', [
                'response' => $response->body()
            ]);

        } catch (\Exception $e) {
            Log::error('Indexing API token error: ' . $e->getMessage());
        }

        return null;
    }

    /**
     * Check if Indexing API is configured
     */
    public function isConfigured(): bool
    {
        $path = config('services.google.indexing_credentials')
            ?? env('GOOGLE_INDEXING_CREDENTIALS');

        return $path && file_exists($path);
    }

    /**
     * Get URL notification status
     */
    public function getUrlStatus(string $url): array
    {
        try {
            $token = $this->getAccessToken();

            if (!$token) {
                return ['error' => 'Could not obtain access token'];
            }

            $response = Http::withToken($token)
                ->get('https://indexing.googleapis.com/v3/urlNotifications/metadata', [
                    'url' => $url
                ]);

            return $response->json();

        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }
}
