<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chats', function (Blueprint $table) {

            $table->id();

            $table->foreignId('chat_room_id')
                ->constrained()
                ->onDelete('cascade');

            $table->foreignId('sender_id')
                ->constrained('users')
                ->onDelete('cascade');

            // Bisa kosong jika hanya kirim gambar
            $table->text('message')->nullable();

            // Path gambar di storage
            $table->string('image_path')->nullable();

            $table->timestamps();

        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chats');
    }
};