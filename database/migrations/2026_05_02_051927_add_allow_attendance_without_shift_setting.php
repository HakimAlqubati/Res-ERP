<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * إضافة إعداد السماح بالحضور بدون وردية
     */
    public function up(): void
    {
        DB::table('settings')->updateOrInsert(
            ['key' => 'allow_attendance_without_shift'],
            [
                'key'   => 'allow_attendance_without_shift',
                'value' => '0',
            ]
        );
    }

    /**
     * حذف الإعداد عند التراجع
     */
    public function down(): void
    {
        DB::table('settings')->where('key', 'allow_attendance_without_shift')->delete();
    }
};
