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
            $table->dropUnique(['asset_tag']); // قد تحتاج إلى استخدام اسم الفهرس إن لزم
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hr_equipment', function (Blueprint $table) {
             $table->unique('asset_tag'); 
        });
    }
};