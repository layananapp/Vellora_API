<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_items', function (Blueprint $table) {

            $table->id();

            $table->foreignId('order_id')
                ->constrained()->onDelete('cascade');

            $table->foreignId('product_id')
                ->nullable()->constrained()->nullOnDelete();

            // Snapshot produk saat checkout (agar tidak berubah walau produk diedit)
            $table->string('product_name');
            $table->string('product_image')->nullable();
            $table->decimal('price', 12, 2);
            $table->integer('qty');
            $table->decimal('subtotal', 12, 2);
            $table->string('variant')->nullable();
            $table->decimal('weight', 8, 2)->default(1);

            // TOKO
            $table->foreignId('store_id')
                ->nullable()->constrained('stores')->nullOnDelete();

            $table->string('store_name')->nullable();
            $table->softDeletes();
            $table->timestamps();

        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};