<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\SignatureInvalidException;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class JwtMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();

        if (!$token) {
            return response()->json([
                'status'  => false,
                'message' => 'Token tidak ditemukan',
            ], 401);
        }

        // ── Blacklist check — SEBELUM decode ────────────────────────────────
        if (Cache::has('jwt_blacklist_' . md5($token))) {
            return response()->json([
                'status'  => false,
                'message' => 'Token tidak valid',
            ], 401);
        }

        try {
            $decoded = JWT::decode(
                $token,
                new Key(config('jwt.secret'), 'HS256')
            );

            $user = User::find($decoded->id);

            if (!$user) {
                return response()->json([
                    'status'  => false,
                    'message' => 'User tidak ditemukan',
                ], 404);
            }

            // Simpan status online user ke Cache (berlaku selama 5 menit)
            try {
                Cache::put('user-online-' . $user->id, true, now()->addMinutes(5));
            } catch (\Exception $e) {
                // Abaikan jika cache bermasalah
            }

            $request->attributes->add(['user' => $user]);

        } catch (ExpiredException $e) {

            Log::warning('[JWT] Token expired', [
                'ip'  => $request->ip(),
                'url' => $request->fullUrl(),
            ]);

            return response()->json([
                'status'  => false,
                'message' => 'Token telah kedaluwarsa, silakan login ulang',
            ], 401);

        } catch (SignatureInvalidException $e) {

            Log::warning('[JWT] Signature invalid', [
                'ip'  => $request->ip(),
                'url' => $request->fullUrl(),
            ]);

            return response()->json([
                'status'  => false,
                'message' => 'Token tidak valid',
            ], 401);

        } catch (\Exception $e) {

            Log::warning('[JWT] Failed to decode token', [
                'ip'    => $request->ip(),
                'url'   => $request->fullUrl(),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'status'  => false,
                'message' => 'Token tidak valid',
            ], 401);
        }

        return $next($request);
    }
}