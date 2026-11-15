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

            $table->foreignId('category_id')
                ->nullable()
                ->constrained('categories')
                ->nullOnDelete();

            // ITEM from Excel — primary business identifier.
            $table->string('item_no')->unique();

            $table->string('name')->nullable();

            $table->string('sku')->nullable();

            $table->string('barcode')->nullable()->index();

            $table->text('description')->nullable();

            $table->enum('availability', [
                'available',
                'unavailable',
            ])->default('available');

            $table->boolean('is_active')->default(true);

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};