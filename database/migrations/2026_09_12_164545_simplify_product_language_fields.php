<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        // No-op.
        // Product language fields are defined directly in create_products_table.
    }

    public function down(): void
    {
        // No-op.
    }
};