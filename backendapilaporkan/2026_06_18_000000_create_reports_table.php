<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('jenis_laporan');
            $table->string('judul');
            $table->text('deskripsi');
            $table->json('foto_bukti')->nullable(); // array path foto, contoh: ["reports/abc.jpg"]
            $table->string('status')->default('pending'); // pending | diproses | selesai
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reports');
    }
};