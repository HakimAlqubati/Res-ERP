<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * تنفيذ التعديلات على قاعدة البيانات.
     */
    public function up(): void
    {
        Schema::table('stock_inventories', function (Blueprint $table) {
            // إضافة حقل نوع الجرد بحيث يقبل القيمة الفارغة (null) للجرد اليدوي
            $table->string('inventory_type')->nullable()->after('inventory_date');

            // إضافة القيد الفريد المركب لمنع التكرار
            $table->unique(
                ['store_id', 'inventory_date', 'inventory_type'], 
                'unique_store_date_type' // اسم القيد في قاعدة البيانات
            );
        });
    }

    /**
     * التراجع عن التعديلات (في حال احتجت للتراجع مستقبلاً).
     */
    public function down(): void
    {
        Schema::table('stock_inventories', function (Blueprint $table) {
            // يجب إزالة القيد الفريد أولاً قبل إزالة الحقل
            $table->dropUnique('unique_store_date_type');
            
            // ثم نقوم بإزالة الحقل
            $table->dropColumn('inventory_type');
        });
    }
};