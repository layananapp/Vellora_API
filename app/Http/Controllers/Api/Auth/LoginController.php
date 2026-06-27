<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Services\JwtService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    private JwtService $jwtService;

    public function __construct(JwtService $jwtService)
    {
        $this->jwtService = $jwtService;
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (Auth::attempt($credentials)) {

            $user = Auth::user();

            // Cek suspensi SEBELUM generate token
            if ($user->is_suspended == 1) {
                Auth::logout();
                return response()->json([
                    'status'  => false,
                    'message' => 'Akun Anda telah disuspend. Silakan hubungi admin.',
                ], 403);
            }

            $ttlHours  = (int) config('jwt.ttl_hours', 2);
            $token     = $this->jwtService->generateToken($user);
            $expiresAt = now()->addHours($ttlHours)->timestamp;

            return response()->json([
                'status'  => true,
                'message' => 'Login berhasil',
                'data'    => [
                    'user'       => $user,
                    'token'      => $token,
                    'expires_at' => $expiresAt,
                ],
            ]);
        }

        return response()->json([
            'status'  => false,
            'message' => 'Email atau Password salah',
        ], 401);
    }

    public function logout(Request $request)
    {
        $token = $request->bearerToken();

        if ($token) {
            $this->jwtService->invalidateToken($token);
        }

        return response()->json([
            'status'  => true,
            'message' => 'Logout berhasil',
        ]);
    }
}