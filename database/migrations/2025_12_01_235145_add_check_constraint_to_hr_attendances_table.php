<?php
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // إضافة قيد يمنع القيم السالبة
        DB::statement("ALTER TABLE hr_attendances ADD CONSTRAINT chk_actual_duration_positive CHECK (actual_duration_hourly >= '00:00:00')");
        
        DB::statement("ALTER TABLE hr_attendances ADD CONSTRAINT chk_total_duration_positive CHECK (total_actual_duration_hourly >= '00:00:00')");
    }

    public function down(): void
    {
        // حذف القيد عند التراجع
        DB::statement("ALTER TABLE hr_attendances DROP CONSTRAINT chk_actual_duration_positive");
        DB::statement("ALTER TABLE hr_attendances DROP CONSTRAINT chk_total_duration_positive");
    }
};