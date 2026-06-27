<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;

use Illuminate\Http\Request;

use App\Models\User;

use Firebase\JWT\JWT;

use Google_Client;

class GoogleAuthController extends Controller
{

    public function googleLogin(
        Request $request
    ) {

        $request->validate([

            'id_token' => ['required']

        ]);

        $client = new Google_Client([

            'client_id' =>
            env('GOOGLE_CLIENT_ID')

        ]);

        $payload = $client->verifyIdToken(

            $request->id_token

        );

        if (!$payload) {

            return response()->json([

                'status' => false,

                'message' =>
                'Google token invalid'

            ], 401);

        }

        $user = User::firstOrCreate(

            [

                'email' =>
                $payload['email']

            ],

            [

                'name' =>
                $payload['name'],

                'password' =>
                bcrypt('google-login')

            ]

        );

        if ($user->is_suspended == 1) {
            return response()->json([
                'status' => false,
                'message' => 'Akun Anda telah disuspend. Silakan hubungi admin.'
            ], 403);
        }

        $token = JWT::encode(

            [

                'iss' => "marketplace",

                'id' => $user->id,

                'iat' => time(),

                'exp' => time() + 86400

            ],

            env('JWT_SECRET_KEY'),

            'HS256'

        );

        return response()->json([

            'status' => true,

            'message' =>
            'Login Google berhasil',

            'data' => [

                'token' => $token,

                'user' => $user

            ]

        ]);

    }

}