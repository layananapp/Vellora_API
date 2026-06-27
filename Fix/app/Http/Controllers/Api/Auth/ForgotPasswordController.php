<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\PasswordResetOtp;
use App\Mail\OtpMail;

class ForgotPasswordController extends Controller
{
    // POST /api/auth/forgot-password
    public function sendOtp(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email', 'exists:users,email']
        ], [
            'email.exists' => 'Email tidak ditemukan'
        ]);

        $user = User::where('email', $request->email)->first();

        PasswordResetOtp::where('email', $request->email)->delete();

        $otp = str_pad(random_int(0, 9999), 4, '0', STR_PAD_LEFT);

        PasswordResetOtp::create([
            'email'      => $request->email,
            'otp'        => $otp,
            'expires_at' => now()->addMinutes(10),
            'is_used'    => false,
        ]);

        // Kirim email — disable SSL verify untuk development
        $httpClient = new \GuzzleHttp\Client(['verify' => false]);
        \Illuminate\Support\Facades\Http::setClient($httpClient);

        Mail::to($request->email)->send(
            new OtpMail($otp, $user->name)
        );

        return response()->json([
            'status'  => true,
            'message' => 'Kode OTP berhasil dikirim ke email kamu',
        ]);
    }

    // POST /api/auth/verify-otp
    public function verifyOtp(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email'],
            'otp'   => ['required', 'digits:4'],
        ]);

        $record = PasswordResetOtp::activeFor($request->email)
            ->where('otp', $request->otp)
            ->first();

        if (! $record) {
            return response()->json([
                'status'  => false,
                'message' => 'Kode OTP salah atau sudah kedaluwarsa',
            ], 422);
        }

        return response()->json([
            'status'  => true,
            'message' => 'OTP valid, silakan buat password baru',
        ]);
    }

    // POST /api/auth/reset-password
    public function resetPassword(Request $request)
    {
        $request->validate([
            'email'    => ['required', 'email'],
            'otp'      => ['required', 'digits:4'],
            'password' => ['required', 'min:8', 'confirmed'],
        ]);

        $record = PasswordResetOtp::activeFor($request->email)
            ->where('otp', $request->otp)
            ->first();

        if (! $record) {
            return response()->json([
                'status'  => false,
                'message' => 'Kode OTP tidak valid atau sudah kedaluwarsa',
            ], 422);
        }

        $user = User::where('email', $request->email)->first();

        if (! $user) {
            return response()->json([
                'status'  => false,
                'message' => 'User tidak ditemukan',
            ], 404);
        }

        $user->update([
            'password' => Hash::make($request->password),
        ]);

        $record->update(['is_used' => true]);

        return response()->json([
            'status'  => true,
            'message' => 'Password berhasil diubah, silakan login',
        ]);
    }

}