<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {

            $table->id();

            $table->foreignId('store_id')
                ->constrained()
                ->onDelete('cascade');

            $table->foreignId('category_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->string('product_name');

            $table->text('description')->nullable();

            $table->decimal('price', 12, 2);

            $table->integer('stock')->default(0);

            $table->boolean('is_active')->default(true);

            // Rating
            $table->decimal('rating_avg', 3, 2)->default(0);

            $table->unsignedInteger('rating_count')->default(0);

            $table->softDeletes();

            $table->timestamps();

        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};