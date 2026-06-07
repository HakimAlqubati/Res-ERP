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
        Schema::table('hr_leave_types', function (Blueprint $table) {
            // إضافة الحقول بـ default لتجنب أخطاء البيانات القديمة
            $table->boolean('requires_attachment')->default(false)->after('is_paid');
            $table->boolean('carry_forward_allowed')->default(false)->after('requires_attachment');
            $table->integer('max_carry_forward')->default(0)->after('carry_forward_allowed');
            $table->boolean('prorate_on_hire')->default(false)->after('max_carry_forward');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hr_leave_types', function (Blueprint $table) {
            $table->dropColumn([
                'requires_attachment',
                'carry_forward_allowed',
                'max_carry_forward',
                'prorate_on_hire'
            ]);
        });
    }
};
