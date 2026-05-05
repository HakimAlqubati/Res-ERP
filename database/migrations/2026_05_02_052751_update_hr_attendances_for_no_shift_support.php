<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * تحديثان لدعم الحضور بدون وردية:
     * 1. إضافة 'no_shift' إلى ENUM حقل status
     * 2. جعل period_id nullable
     */
    public function up(): void
    {
        // 1. إضافة القيمة الجديدة إلى ENUM
        // Laravel لا يدعم تعديل ENUM مباشرة، نستخدم DB::statement
        DB::statement("
            ALTER TABLE hr_attendances
            MODIFY COLUMN status ENUM(
                'on_time',
                'late_arrival',
                'early_arrival',
                'early_departure',
                'late_departure',
                'no_shift'
            ) NULL
        ");

        // 2. جعل period_id nullable
        Schema::table('hr_attendances', function (Blueprint $table) {
            $table->bigInteger('period_id')->nullable()->change();
        });
    }

    /**
     * التراجع: إزالة 'no_shift' من ENUM وإعادة period_id إلى NOT NULL
     * 
     * تحذير: سيفشل التراجع إذا كانت توجد سجلات بـ status='no_shift' أو period_id=null
     */
    public function down(): void
    {
        DB::statement("
            ALTER TABLE hr_attendances
            MODIFY COLUMN status ENUM(
                'on_time',
                'late_arrival',
                'early_arrival',
                'early_departure',
                'late_departure'
            ) NULL
        ");

        Schema::table('hr_attendances', function (Blueprint $table) {
            $table->bigInteger('period_id')->nullable(false)->change();
        });
    }
};
