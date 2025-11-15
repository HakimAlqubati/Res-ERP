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
        /**
         * جدول رأس عملية البيع POS
         */
        Schema::create('pos_sales', function (Blueprint $table) {
            $table->id();

            // علاقات أساسية
            $table->foreignId('branch_id')
                ->constrained()
                ->restrictOnDelete();

            $table->foreignId('store_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            // معلومات السند
            $table->dateTime('sale_date')->nullable();
            $table->string('status')->default('completed'); // مثال: completed, cancelled, draft

            // إجماليات بسيطة
            $table->decimal('total_quantity', 15, 4)->nullable();
            $table->decimal('total_amount', 15, 4)->nullable();

            // إلغاء السند
            $table->boolean('cancelled')->default(false);
            $table->string('cancel_reason')->nullable();

            // ملاحظات
            $table->text('notes')->nullable();

            // تتبع المستخدمين
            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignId('updated_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->softDeletes();
            $table->timestamps();
        });

        /**
         * جدول تفاصيل البيع POS
         */
        Schema::create('pos_sale_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('pos_sale_id')
                ->constrained('pos_sales')
                ->cascadeOnDelete();

            $table->foreignId('product_id')
                ->constrained()
                ->restrictOnDelete();

            $table->foreignId('unit_id')
                ->constrained()
                ->restrictOnDelete();

            // package_size بدقة 4 منازل عشرية
            $table->decimal('package_size', 15, 4)->default(1);


            // 4 أرقام بعد الفاصلة
            $table->decimal('quantity', 15, 4);
            $table->decimal('price', 15, 4);

            $table->decimal('total_price', 15, 4); // quantity * price


          
            // ملاحظات
            $table->text('notes')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pos_sale_items');
        Schema::dropIfExists('pos_sales');
    }
};
