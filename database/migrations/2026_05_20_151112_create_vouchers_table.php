<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('vouchers', function (Blueprint $table) {

            $table->id();

            $table->string('code')
                ->unique();

            $table->string('voucher_name');

            $table->enum('discount_type', [
                'percentage',
                'fixed'
            ]);

            $table->decimal('discount_value', 12, 2);

            $table->decimal('minimum_transaction', 12, 2)
                ->default(0);

            $table->integer('quota')
                ->default(0);

            $table->integer('used')
                ->default(0);

            $table->timestamp('expired_at')
                ->nullable();

            $table->boolean('is_active')
                ->default(true);

            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vouchers');
    }
};