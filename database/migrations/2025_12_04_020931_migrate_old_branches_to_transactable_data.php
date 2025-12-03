<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use App\Models\Branch; // تأكد من استدعاء الموديل الصحيح أو كتابة المسار نصاً

return new class extends Migration
{
    public function up()
    {
        // نستخدم DB Facade بدلاً من Eloquent لضمان السرعة وعدم التأثر بتغييرات الموديل مستقبلاً
        
        // 1. ترحيل البيانات حيث يوجد branch_id
        DB::table('financial_transactions')
            ->whereNotNull('branch_id')
            ->whereNull('transactable_id') // فقط السجلات التي لم يتم ترحيلها
            ->update([
                'transactable_type' => Branch::class, // أو 'App\Models\Branch'
                'transactable_id'   => DB::raw('branch_id') // نسخ القيمة مباشرة من العمود القديم
            ]);
    }

    public function down()
    {
        // التراجع: تصفير الحقول الجديدة للسجلات التي كانت تعتمد على الفروع
        DB::table('financial_transactions')
            ->where('transactable_type', Branch::class)
            ->update([
                'transactable_type' => null,
                'transactable_id'   => null
            ]);
    }
};