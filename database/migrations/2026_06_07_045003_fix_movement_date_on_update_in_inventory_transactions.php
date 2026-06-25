<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // استخدام DB statement لأن تغيير الـ timestamp المعقد أحياناً يواجه مشاكل في Eloquent/Doctrine
        // هذا الأمر سيزيل `ON UPDATE CURRENT_TIMESTAMP`
        DB::statement('ALTER TABLE `inventory_transactions` MODIFY `movement_date` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('ALTER TABLE `inventory_transactions` MODIFY `movement_date` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP');
    }
};
