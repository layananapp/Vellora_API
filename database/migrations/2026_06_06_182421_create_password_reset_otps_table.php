<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('password_reset_otps', function (Blueprint $table) {

            $table->id();

            // Email yang minta reset password
            $table->string('email')->index();

            // Kode OTP 4 digit
            $table->string('otp', 4);

            // OTP kedaluwarsa setelah 10 menit
            $table->timestamp('expires_at');

            // Tandai sudah dipakai
            $table->boolean('is_used')->default(false);

            $table->timestamps();

        });
    }

    public function down(): void
    {
        Schema::dropIfExists('password_reset_otps');
    }
};