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
        Schema::create('import_batch_items', function (Blueprint $table) {
            $table->id();

            // ملف الاستيراد الذي ينتمي إليه هذا الصف
            $table->foreignId('import_batch_id')
                ->constrained('import_batches')
                ->cascadeOnDelete();

            // رقم الصف في Excel
            $table->unsignedInteger('row_number');

            // معلومات تساعدنا في التتبع والتحقق
            $table->string('item_no')->nullable();
            $table->string('category_code')->nullable();

            // المنتج الذي نتج عن استيراد الصف، إن نجح
            $table->foreignId('product_id')
                ->nullable()
                ->constrained('products')
                ->nullOnDelete();

            // نتيجة معالجة الصف
            $table->enum('status', [
                'pending',
                'success',
                'failed',
                'skipped',
            ])->default('pending');

            // رسالة الخطأ إن وجدت
            $table->text('error_message')->nullable();

            // الاحتفاظ بالبيانات الأصلية للصف
            $table->json('raw_data')->nullable();

            $table->timestamps();

            // منع تكرار نفس الصف داخل نفس عملية الاستيراد
            $table->unique(
                ['import_batch_id', 'row_number'],
                'import_batch_row_unique'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('import_batch_items');
    }
};