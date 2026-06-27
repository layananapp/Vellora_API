<?php

namespace App\Services;

use App\Models\User;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class JwtService
{
    /**
     * Generate a signed JWT token for the given user.
     * TTL diambil dari config('jwt.ttl_hours') supaya bisa di-override via .env.
     */
    public function generateToken(User $user): string
    {
        $ttlHours = (int) config('jwt.ttl_hours', 2);

        $payload = [
            'iss'  => 'marketplace',
            'id'   => $user->id,
            'name' => $user->name,
            'role' => $user->role,
            'iat'  => now()->timestamp,
            'exp'  => now()->addHours($ttlHours)->timestamp,
        ];

        return JWT::encode(
            $payload,
            config('jwt.secret'),
            'HS256'
        );
    }

    /**
     * Masukkan token ke blacklist di Cache.
     * TTL cache disesuaikan dengan sisa waktu token (agar tidak membengkak terus).
     */
    public function invalidateToken(string $token): void
    {
        try {
            // Decode tanpa verifikasi waktu supaya bisa dapat exp meskipun sudah expired
            $decoded = JWT::decode(
                $token,
                new Key(config('jwt.secret'), 'HS256')
            );

            $exp     = $decoded->exp ?? now()->addHours(config('jwt.ttl_hours', 2))->timestamp;
            $ttl     = max(1, $exp - now()->timestamp);

            Cache::put(
                'jwt_blacklist_' . md5($token),
                true,
                $ttl
            );
        } catch (\Exception $e) {
            // Jika token tidak bisa di-decode (misal sudah expired sebelumnya),
            // simpan di cache dengan TTL default agar tetap ter-blacklist.
            $ttl = config('jwt.ttl_hours', 2) * 3600;
            Cache::put(
                'jwt_blacklist_' . md5($token),
                true,
                $ttl
            );

            Log::info('[JWT] invalidateToken: token tidak bisa di-decode, blacklist dengan TTL default.', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Cek apakah token ada di blacklist.
     */
    public function isBlacklisted(string $token): bool
    {
        return Cache::has('jwt_blacklist_' . md5($token));
    }
}
