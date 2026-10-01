<?php

namespace App\Services;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class FirebaseMessaging
{
    public function enabled(): bool
    {
        return (bool) config('firebase.enabled')
            && filled(config('firebase.vapid_key'))
            && filled(config('firebase.web.projectId'))
            && is_readable((string) config('firebase.credentials'));
    }

    /** @param array<string, string> $data */
    public function send(string $token, array $data): Response
    {
        return $this->request([
            'token' => $token,
            'data' => $data,
            'webpush' => ['headers' => ['TTL' => '86400', 'Urgency' => 'high']],
        ]);
    }

    public function check(): void
    {
        $response = $this->request([
            'topic' => 'configuration-check',
            'data' => ['title' => 'Configuration check'],
        ], true);
        if (! $response->successful()) {
            throw new RuntimeException('Firebase validation failed (HTTP '.$response->status().', '.($response->json('error.status') ?? 'unknown').').');
        }
    }

    /** @param array<string, mixed> $message */
    private function request(array $message, bool $validateOnly = false): Response
    {
        if (! $this->enabled()) {
            throw new RuntimeException('Firebase push is disabled or its configuration is incomplete.');
        }

        $project = rawurlencode((string) config('firebase.web.projectId'));
        $response = Http::withToken($this->accessToken())->connectTimeout(5)->timeout(15)
            ->post("https://fcm.googleapis.com/v1/projects/{$project}/messages:send", [
                'validate_only' => $validateOnly, 'message' => $message,
            ]);
        if ($response->status() === 401) {
            Cache::forget($this->cacheKey());
        }

        return $response;
    }

    private function cacheKey(): string
    {
        return 'firebase.oauth.'.hash('sha256', config('firebase.web.projectId').'|'.config('firebase.credentials').'|'.@filemtime((string) config('firebase.credentials')));
    }

    private function accessToken(): string
    {
        return Cache::remember($this->cacheKey(), 3300, function (): string {
            $credentials = json_decode(file_get_contents(config('firebase.credentials')), true, flags: JSON_THROW_ON_ERROR);
            if (($credentials['type'] ?? '') !== 'service_account'
                || ($credentials['project_id'] ?? '') !== config('firebase.web.projectId')) {
                throw new RuntimeException('Firebase service-account project does not match configuration.');
            }
            $header = $this->base64Url(json_encode(['alg' => 'RS256', 'typ' => 'JWT'], JSON_THROW_ON_ERROR));
            $claims = $this->base64Url(json_encode([
                'iss' => $credentials['client_email'],
                'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
                'aud' => 'https://oauth2.googleapis.com/token',
                'iat' => time(), 'exp' => time() + 3600,
            ], JSON_THROW_ON_ERROR));
            if (! openssl_sign($header.'.'.$claims, $signature, $credentials['private_key'], OPENSSL_ALGO_SHA256)) {
                throw new RuntimeException('Unable to sign the Firebase authentication request.');
            }
            $response = Http::asForm()->connectTimeout(5)->timeout(15)->post('https://oauth2.googleapis.com/token', [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion' => $header.'.'.$claims.'.'.$this->base64Url($signature),
            ]);
            if (! $response->successful() || ! is_string($response->json('access_token'))) {
                throw new RuntimeException('Firebase authentication failed (HTTP '.$response->status().').');
            }

            return $response->json('access_token');
        });
    }

    private function base64Url(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }
}
