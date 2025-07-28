<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
       public function up(): void
    {
        // 1. أضف العمود بشكل مؤقت nullable (بدون unique)
        Schema::table('hr_equipment_types', function (Blueprint $table) {
            $table->string('code')->nullable()->after('name');
        });

        // 2. توليد slug فريد لكل سجل قديم وملء القيمة
        $types = DB::table('hr_equipment_types')->get();

        foreach ($types as $type) {
            $baseSlug = strtoupper(Str::slug($type->name, '-'));
            $slug = $baseSlug;
            $i = 1;

            // ضمان تفرد القيمة
            while (DB::table('hr_equipment_types')->where('code', $slug)->exists()) {
                $slug = $baseSlug . '-' . $i++;
            }

            DB::table('hr_equipment_types')->where('id', $type->id)->update(['code' => $slug]);
        }

        // 3. جعل العمود غير قابل للنول
        Schema::table('hr_equipment_types', function (Blueprint $table) {
            $table->string('code')->nullable(false)->change();
        });

        // 4. إضافة قيد UNIQUE
        Schema::table('hr_equipment_types', function (Blueprint $table) {
            $table->unique('code');
        });
    }

    public function down(): void
    {
        Schema::table('hr_equipment_types', function (Blueprint $table) {
            $table->dropUnique(['code']);
            $table->dropColumn('code');
        });
    }
};