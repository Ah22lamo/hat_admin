<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ImportBatchItem extends Model
{
    protected $fillable = [
        'import_batch_id',
        'row_number',

        // Original identification
        'item_no',
        'item_code',
        'category_code',

        // Related product
        'product_id',

        // Processing result
        'status',
        'error_message',
        'warnings',

        // Original Excel row
        'raw_data',

        // Excel quantity
        'cartons_count',
        'qty_per_carton',
        'total_quantity',

        // FOB pricing
        'unit_price_fob',
        'subtotal_price',

        // Shipping
        'cbm_per_carton',
        'total_cbm',
        'total_weight',

        // Landed cost
        'landed_unit_cost_350',
        'landed_carton_cost_350',
        'landed_unit_cost_300',
        'landed_carton_cost_300',

        // Image
        'image_path',

        // Shipping calculation
        'shipping_rate',
    ];

    protected $casts = [
        'warnings' => 'array',
        'raw_data' => 'array',

        'unit_price_fob' => 'decimal:4',
        'subtotal_price' => 'decimal:4',

        'cbm_per_carton' => 'decimal:6',
        'total_cbm' => 'decimal:6',
        'total_weight' => 'decimal:3',

        'landed_unit_cost_350' => 'decimal:4',
        'landed_carton_cost_350' => 'decimal:4',
        'landed_unit_cost_300' => 'decimal:4',
        'landed_carton_cost_300' => 'decimal:4',

        'shipping_rate' => 'decimal:2',
    ];

    public function importBatch(): BelongsTo
    {
        return $this->belongsTo(ImportBatch::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}