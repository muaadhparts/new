<?php

namespace App\Domain\Catalog\Services;

use App\Models\Token;
use App\Models\TokenLog;
use App\Models\NissanCredential;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Carbon;

class NissanTokenService
{
    public static function refresh()
    {
        $refreshToken = Token::latestRefreshToken();

        if (!$refreshToken) {
            self::log('refresh', 'failed', 'No refresh token found, fallback to login');
            return self::login();
        }

        $credentials = NissanCredential::first();

        if (!$credentials) {
            self::log('refresh', 'failed', 'Credentials not found during refresh');
            return null;
        }

        $response = Http::withHeaders([
            'Accept' => 'application/json',
        ])->asForm()->post('https://api.superservice.com/v1/auth/login', [
            'username'          => $credentials->email,
            'password'          => $credentials->password,
            'refreshToken'      => $refreshToken,
            'createRefreshToken'=> 'true',
        ]);

        if ($response->successful()) {
            self::log('refresh', 'success', 'Token refreshed successfully');
            return self::storeTokenOnly($response, $credentials);
        }

        self::log('refresh', 'failed', "Refresh failed: " . $response->body());
        return self::login();
    }

    public static function login()
    {
        $credentials = NissanCredential::first();

        if (!$credentials) {
            self::log('login', 'failed', 'Credentials not found');
            return null;
        }

        $response = Http::withHeaders([
            'Accept' => 'application/json',
        ])->asForm()->post('https://api.superservice.com/v1/auth/login', [
            'username'           => $credentials->email,
            'password'           => $credentials->password,
            'createRefreshToken' => 'true',
        ]);

        if ($response->successful()) {
            self::log('login', 'success', 'Login successful');
            return self::storeTokenOnly($response, $credentials);
        }

        self::log('login', 'failed', 'Login failed: ' . $response->body());
        return null;
    }

    private static function storeTokenOnly($response, $credentials)
    {
        $data = $response->json();

        // 🔍 تحقق من الكوكي الجديد
        $newCookies = $response->headers()['Set-Cookie'] ?? null;
        $currentCookie = $credentials->cookie;
        $extractedNewCookie = is_array($newCookies) ? implode('; ', $newCookies) : $newCookies;

        // ✅ إذا تغيّر الكوكي، خزنه في قاعدة البيانات
        if (!empty($extractedNewCookie) && $extractedNewCookie !== $currentCookie) {
            self::log('cookie', 'changed', 'Cookie changed and updated in database.');
            $credentials->cookie = $extractedNewCookie;
            $credentials->save();
        }

        // 🟢 خزّن فقط التوكنات
        return Token::create([
            'accessToken'  => $data['accessToken'] ?? $data['access_token'],
            'refreshToken' => $data['refreshToken'] ?? $data['refresh_token'],
            'expires_at'   => Carbon::now()->addSeconds($data['expiresInSeconds'] ?? $data['expires_in']),
        ]);
    }


    protected static function log(string $type, string $status, string $message = null)
    {
        TokenLog::create([
            'type'        => $type,
            'status'      => $status,
            'message'     => $message,
            'executed_at' => now(),
        ]);
    }
}
