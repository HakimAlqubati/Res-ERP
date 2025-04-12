<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // ✅ تعديل enum وإضافة "popup"
        DB::statement("ALTER TABLE branches MODIFY COLUMN type ENUM('branch', 'central_kitchen', 'hq', 'popup')");

        Schema::table('branches', function (Blueprint $table) {
            // ✅ إضافة start_date و end_date
            $table->dateTime('start_date')->nullable()->after('type');
            $table->dateTime('end_date')->nullable()->after('start_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // ❗️ لاحظ أن الرجوع في enum يتطلب حذف القيمة الجديدة
        DB::statement("ALTER TABLE branches MODIFY COLUMN type ENUM('branch', 'central_kitchen', 'hq')");

        Schema::table('branches', function (Blueprint $table) {
            $table->dropColumn(['start_date', 'end_date']);
        });
    }
};
