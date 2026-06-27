<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Services\JwtService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Models\User;
use Google_Client;

class GoogleAuthController extends Controller
{
    private JwtService $jwtService;

    public function __construct(JwtService $jwtService)
    {
        $this->jwtService = $jwtService;
    }

    public function googleLogin(Request $request)
    {
        $request->validate([
            'id_token' => ['required'],
        ]);

        $client = new Google_Client([
            'client_id' => config('services.google.client_id'),
        ]);

        $payload = $client->verifyIdToken($request->id_token);

        if (!$payload) {
            return response()->json([
                'status'  => false,
                'message' => 'Google token invalid',
            ], 401);
        }

        $user = User::firstOrCreate(
            [
                'email' => $payload['email'],
            ],
            [
                'name'         => $payload['name'],
                'role'         => 'buyer',                      // Default role untuk user baru
                'password'     => Hash::make(Str::random(32)),  // Password aman (bukan hardcoded)
                'is_suspended' => false,
            ]
        );

        // Cek suspensi SEBELUM generate token
        if ($user->is_suspended == 1) {
            return response()->json([
                'status'  => false,
                'message' => 'Akun Anda telah disuspend. Silakan hubungi admin.',
            ], 403);
        }

        // Gunakan JwtService: konsisten TTL dengan login biasa
        $token = $this->jwtService->generateToken($user);

        return response()->json([
            'status'  => true,
            'message' => 'Login Google berhasil',
            'data'    => [
                'token' => $token,
                'user'  => $user,
            ],
        ]);
    }
}