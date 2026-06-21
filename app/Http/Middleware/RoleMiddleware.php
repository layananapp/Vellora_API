<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, $role): Response
    {
        $user = $request->get('user');

        if (!$user) {

            return response()->json([
                'status' => false,
                'message' => 'User tidak ditemukan'
            ], 401);
        }

        if ($user->role != $role) {

            return response()->json([
                'status' => false,
                'message' => 'Akses ditolak'
            ], 403);
        }

        return $next($request);
    }
}