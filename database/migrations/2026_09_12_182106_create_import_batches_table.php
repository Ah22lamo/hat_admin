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
        Schema::create('import_batches', function (Blueprint $table) {
            $table->id();

            // بيانات الملف
            $table->string('file_name');
            $table->string('file_path');

            // نوع الاستيراد
            $table->string('import_type')->default('products');

            // إحصائيات الاستيراد
            $table->unsignedInteger('total_rows')->default(0);
            $table->unsignedInteger('successful_rows')->default(0);
            $table->unsignedInteger('failed_rows')->default(0);

            // حالة العملية
            $table->enum('status', [
                'pending',
                'processing',
                'completed',
                'completed_with_errors',
                'failed',
            ])->default('pending');

            // المستخدم الذي نفذ الاستيراد
            $table->foreignId('imported_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            // الزمن
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('import_batches');
    }
};