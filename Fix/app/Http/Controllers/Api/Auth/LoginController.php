<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Firebase\JWT\JWT;

class LoginController extends Controller
{
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required']
        ]);

        if (Auth::attempt($credentials)) {

            $user = Auth::user();

            if ($user->is_suspended == 1) {
                Auth::logout();
                return response()->json([
                    'status' => 'error',
                    'message' => 'Akun Anda telah disuspend. Silakan hubungi admin.'
                ], 403);
            }

            $payload = [
                'id' => $user->id,
                'name' => $user->name,
                'role' => $user->role,
                'iat' => now()->timestamp,
                'exp' => now()->addHours(2)->timestamp
            ];

            $token = JWT::encode(
                $payload,
                env('JWT_SECRET_KEY'),
                'HS256'
            );

            return response()->json([
                'status' => 'success',
                'message' => 'Login berhasil',
                'data' => [
                    'user' => $user,
                    'token' => $token,
                    'expires_at' => now()->addHours(2)->timestamp
                ]
            ]);
        }

        return response()->json([
            'status' => 'error',
            'message' => 'Email atau Password salah'
        ], 401);
    }

        public function logout()
    {
        return response()->json([
            'status' => true,
            'message' => 'Logout berhasil'
        ]);
    }
}