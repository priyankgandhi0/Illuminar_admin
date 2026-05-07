<?php

namespace App\Services;

use Firebase\JWT\JWT;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class GoogleAccessTokenService
{
    private const CACHE_KEY = 'firebase_access_token';
    private const CACHE_TTL = 3500;

    public static function generate(): string
    {
        return Cache::remember(self::CACHE_KEY, self::CACHE_TTL, function () {

            $path = storage_path('app/firebase/service-account.json');

            if (!file_exists($path)) {
                throw new RuntimeException('Firebase service account file missing.');
            }

            $credentials = json_decode(file_get_contents($path), true);

            $now = time();

            $payload = [
                'iss'   => $credentials['client_email'],
                'scope' => 'https://www.googleapis.com/auth/datastore https://www.googleapis.com/auth/firebase.messaging',
                'aud'   => 'https://oauth2.googleapis.com/token',
                'iat'   => $now,
                'exp'   => $now + 3600,
            ];

            $jwt = JWT::encode(
                $payload,
                $credentials['private_key'],
                'RS256'
            );

            $response = Http::asForm()
                ->timeout(10)
                ->post('https://oauth2.googleapis.com/token', [
                    'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                    'assertion'  => $jwt,
                ]);

            if (!$response->successful()) {
                throw new RuntimeException(
                    'Failed to generate Google access token: ' . $response->body()
                );
            }

            $token = $response->json('access_token');

            if (!$token) {
                throw new RuntimeException('Access token not found in response.');
            }

            return $token;
        });
    }

    public static function forget(): void
    {
        Cache::forget(self::CACHE_KEY);
    }
}