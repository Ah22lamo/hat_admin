<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        // No-op.
        // brand_id is not part of the final HAT product schema.
    }

    public function down(): void
    {
        // No-op.
    }
};