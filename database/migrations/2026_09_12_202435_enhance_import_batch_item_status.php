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
        Schema::table('import_batch_items', function (Blueprint $table) {
            $table->enum('status', [
                'pending',
                'success',
                'success_with_warning',
                'failed',
                'skipped',
            ])->default('pending')->change();

            $table->json('warnings')
                ->nullable()
                ->after('error_message');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('import_batch_items', function (Blueprint $table) {
            $table->enum('status', [
                'pending',
                'success',
                'failed',
                'skipped',
            ])->default('pending')->change();

            $table->dropColumn('warnings');
        });
    }
};