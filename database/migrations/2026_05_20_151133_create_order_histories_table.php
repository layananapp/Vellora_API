<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ORDER HISTORIES
        Schema::create('order_histories', function (Blueprint $table) {

            $table->id();

            $table->foreignId('order_id')
                ->constrained()->onDelete('cascade');

            $table->string('status');

            $table->text('description')->nullable();

            $table->timestamps();

        });

    }

    public function down(): void
    {
        Schema::dropIfExists('payment_logs');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('order_histories');
    }
};