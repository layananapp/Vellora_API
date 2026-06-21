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

            $payload = [
                'id' => Auth::user()->id,
                'name' => Auth::user()->name,
                'role' => Auth::user()->role,
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
                    'user' => Auth::user(),
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