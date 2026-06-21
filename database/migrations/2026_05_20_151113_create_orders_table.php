<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {

            $table->id();

            $table->foreignId('user_id')
                ->constrained()->onDelete('cascade');

            $table->foreignId('address_id')
                ->constrained('user_addresses')->onDelete('restrict');

            // PEMBAYARAN
            $table->enum('payment_method', [
                'COD', 'QRIS', 'Transfer Bank'
            ]);

            $table->string('bank_name')->nullable();

            // VOUCHER
            $table->foreignId('voucher_id')
                ->nullable()->constrained('vouchers')->nullOnDelete();

            $table->decimal('voucher_discount', 12, 2)->default(0);

            // BIAYA
            $table->decimal('shipping_cost', 12, 2)->default(0);

            $table->decimal('product_subtotal', 12, 2)->default(0);

            $table->decimal('total_amount', 12, 2)->default(0);

            // STATUS: pending_payment | processing | shipped | delivered | cancelled | waiting_verification
            $table->string('status')->default('pending_payment');

            // PENGIRIMAN
            $table->string('courier')->nullable();
            $table->string('receipt_number')->nullable();

            // BATAS WAKTU BAYAR
            $table->timestamp('payment_expired_at')->nullable();

            $table->text('notes')->nullable();

            $table->timestamps();
            $table->softDeletes();

        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};