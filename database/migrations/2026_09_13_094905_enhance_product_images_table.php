<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_images', function (Blueprint $table) {

            $table->foreignId('product_id')
                ->after('id')
                ->constrained('products')
                ->cascadeOnDelete();

            $table->string('path');
            $table->string('disk')->default('public');

            $table->string('alt_text')->nullable();

            $table->boolean('is_primary')
                ->default(false);

            $table->unsignedInteger('sort_order')
                ->default(0);

            $table->string('source')
                ->nullable();

            $table->index([
                'product_id',
                'is_primary',
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('product_images', function (Blueprint $table) {
            $table->dropForeign(['product_id']);
            $table->dropIndex([
                'product_id',
                'is_primary',
            ]);

            $table->dropColumn([
                'product_id',
                'path',
                'disk',
                'alt_text',
                'is_primary',
                'sort_order',
                'source',
            ]);
        });
    }
};