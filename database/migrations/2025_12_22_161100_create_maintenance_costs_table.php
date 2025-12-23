<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * جدول تكاليف الصيانة
     * يدعم علاقة polymorphic مع ServiceRequest و Equipment
     */
    public function up(): void
    {
        return;
        Schema::create('hr_maintenance_costs', function (Blueprint $table) {
            $table->id();

            // المبلغ والوصف
            $table->decimal('amount', 10, 2);
            $table->string('description')->nullable();

            // نوع التكلفة
            $table->enum('cost_type', ['repair', 'parts', 'labor', 'purchase', 'other'])->default('repair');

            // Polymorphic relation - يشير إلى Equipment أو ServiceRequest
            $table->morphs('costable'); // costable_type, costable_id

            // معلومات إضافية
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->date('cost_date')->nullable();

            // علامة المزامنة مع النظام المالي
            $table->boolean('synced_to_financial')->default(false);

            $table->timestamps();
            $table->softDeletes();

            // فهرس للبحث السريع (morphs already creates index for costable)
            $table->index('cost_type');
            $table->index('synced_to_financial');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hr_maintenance_costs');
    }
};
