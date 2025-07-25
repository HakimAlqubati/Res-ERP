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
        Schema::create('hr_employee_face_data', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('employee_id')->index(); // مرجع داخلي فقط
            $table->string('employee_name');
            $table->string('employee_email')->index();
            $table->unsignedBigInteger('employee_branch_id')->nullable();
            $table->string('image_path');             // يمكن أن يكون URL أو مسار داخلي
            $table->json('embedding');                // بصمة الوجه بصيغة JSON
            $table->boolean('active')->default(true); // حالة الصورة
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hr_employee_face_data');
    }
};