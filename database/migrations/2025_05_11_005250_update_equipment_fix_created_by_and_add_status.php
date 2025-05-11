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
        Schema::table('hr_equipment', function (Blueprint $table) {
            if (Schema::hasColumn('hr_equipment', 'creatd_by')) {
                $table->renameColumn('creatd_by', 'created_by');
            }
            $table->enum('status', ['Active', 'Under Maintenance', 'Retired'])
                ->default('Active')
                ->after('next_service_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hr_equipment', function (Blueprint $table) {
            // الرجوع عن الحقل status
            $table->dropColumn('status');

            // إعادة التسمية القديمة (إن لزم الرجوع)
            if (Schema::hasColumn('hr_equipment', 'created_by')) {
                $table->renameColumn('created_by', 'creatd_by');
            }
        });
    }
};
