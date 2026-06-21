<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use App\Models\User;

class JwtMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        
        $token = $request->bearerToken();

        if (!$token) {
            return response()->json([
                'status' => false,
                'message' => 'Token tidak ditemukan'
            ], 401);
        }

        try {

            $decoded = JWT::decode(
                $token,
                new Key(env('JWT_SECRET_KEY'), 'HS256')
            );

            $user = User::find($decoded->id);

            if (!$user) {
                return response()->json([
                    'status' => false,
                    'message' => 'User tidak ditemukan'
                ], 404);
            }

            $request->attributes->add([
                'user' => $user
            ]);

        } catch (\Exception $e) {

            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 401);
        }

        return $next($request);
    }
    
}