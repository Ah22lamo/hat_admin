<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('import_batch_items', function (Blueprint $table) {

            // Excel identification
            $table->string('item_code')
                ->nullable()
                ->after('item_no');

            // Quantity
            $table->unsignedInteger('cartons_count')
                ->nullable();

            $table->unsignedInteger('qty_per_carton')
                ->nullable();

            $table->unsignedInteger('total_quantity')
                ->nullable();

            // FOB prices
            $table->decimal('unit_price_fob', 15, 4)
                ->nullable();

            $table->decimal('subtotal_price', 15, 4)
                ->nullable();

            // Shipping dimensions
            $table->decimal('cbm_per_carton', 12, 6)
                ->nullable();

            $table->decimal('total_cbm', 12, 6)
                ->nullable();

            $table->decimal('total_weight', 15, 3)
                ->nullable();

            // Landed Cost - $350 / CBM
            $table->decimal('landed_unit_cost_350', 15, 4)
                ->nullable();

            $table->decimal('landed_carton_cost_350', 15, 4)
                ->nullable();

            // Landed Cost - $300 / CBM
            $table->decimal('landed_unit_cost_300', 15, 4)
                ->nullable();

            $table->decimal('landed_carton_cost_300', 15, 4)
                ->nullable();

            // Image extracted from Excel
            $table->string('image_path')
                ->nullable();

            // Useful for debugging/import auditing
            $table->decimal('shipping_rate', 12, 2)
                ->nullable();

            $table->index('item_code');
            $table->index('category_code');
            $table->index('product_id');
        });
    }

    public function down(): void
    {
        Schema::table('import_batch_items', function (Blueprint $table) {

            $table->dropIndex(['item_code']);
            $table->dropIndex(['category_code']);
            $table->dropIndex(['product_id']);

            $table->dropColumn([
                'item_code',
                'cartons_count',
                'qty_per_carton',
                'total_quantity',
                'unit_price_fob',
                'subtotal_price',
                'cbm_per_carton',
                'total_cbm',
                'total_weight',
                'landed_unit_cost_350',
                'landed_carton_cost_350',
                'landed_unit_cost_300',
                'landed_carton_cost_300',
                'image_path',
                'shipping_rate',
            ]);
        });
    }
};