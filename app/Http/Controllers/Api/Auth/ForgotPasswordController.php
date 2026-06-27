<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Cache;
use App\Models\User;
use App\Models\PasswordResetOtp;
use App\Mail\OtpMail;

class ForgotPasswordController extends Controller
{
    // POST /api/auth/forgot-password
    public function sendOtp(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        // ── Resend cooldown (60 detik per email) ────────────────────────────
        $cooldownKey = 'otp_cooldown_' . $request->email;
        if (Cache::has($cooldownKey)) {
            return response()->json([
                'status'  => false,
                'message' => 'Tunggu 60 detik sebelum request OTP baru',
            ], 429);
        }

        // ── Generic response: tidak expose apakah email terdaftar ────────────
        // Proses pengiriman hanya dilakukan jika email ada di database,
        // tapi response selalu sama untuk mencegah user enumeration.
        $user = User::where('email', $request->email)->first();

        if ($user) {
            PasswordResetOtp::where('email', $request->email)->delete();

            // OTP tetap 4 digit sesuai kebutuhan Ionic (4 input box)
            $otp = str_pad(random_int(0, 9999), 4, '0', STR_PAD_LEFT);

            PasswordResetOtp::create([
                'email'      => $request->email,
                'otp'        => $otp,
                'expires_at' => now()->addMinutes(10),
                'is_used'    => false,
            ]);

            // Kirim email — SSL verify aktif (tidak disable global)
            Mail::to($request->email)->send(
                new OtpMail($otp, $user->name)
            );
        }

        // Set cooldown SETELAH proses, agar cooldown berlaku untuk semua request
        Cache::put($cooldownKey, true, 60);

        // Selalu return pesan generik, tidak expose eksistensi email
        return response()->json([
            'status'  => true,
            'message' => 'Jika email terdaftar, kode OTP akan dikirimkan',
        ]);
    }

    // POST /api/auth/verify-otp
    public function verifyOtp(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email'],
            'otp'   => ['required', 'digits:4'],
        ]);

        // ── Attempt tracking (maks 5x, lock 15 menit) ───────────────────────
        $attemptsKey = 'otp_attempts_' . $request->email;
        $attempts    = Cache::get($attemptsKey, 0);

        if ($attempts >= 5) {
            return response()->json([
                'status'  => false,
                'message' => 'Terlalu banyak percobaan. Tunggu 15 menit.',
            ], 429);
        }

        $record = PasswordResetOtp::activeFor($request->email)
            ->where('otp', $request->otp)
            ->first();

        if (!$record) {
            // Increment attempt counter
            Cache::put($attemptsKey, $attempts + 1, now()->addMinutes(15));

            return response()->json([
                'status'  => false,
                'message' => 'Kode OTP salah atau sudah kedaluwarsa',
            ], 422);
        }

        // OTP valid — hapus attempt counter
        Cache::forget($attemptsKey);

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

        if (!$record) {
            return response()->json([
                'status'  => false,
                'message' => 'Kode OTP tidak valid atau sudah kedaluwarsa',
            ], 422);
        }

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json([
                'status'  => false,
                'message' => 'User tidak ditemukan',
            ], 404);
        }

        $user->update([
            'password' => Hash::make($request->password),
        ]);

        // Hapus SEMUA record OTP untuk email ini (bukan hanya mark is_used)
        PasswordResetOtp::where('email', $request->email)->delete();

        // Hapus juga attempt counter jika masih ada
        Cache::forget('otp_attempts_' . $request->email);
        Cache::forget('otp_cooldown_' . $request->email);

        return response()->json([
            'status'  => true,
            'message' => 'Password berhasil diubah, silakan login',
        ]);
    }
}