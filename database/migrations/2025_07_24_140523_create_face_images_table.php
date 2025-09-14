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
        Schema::create('face_images', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable(); // لو مرتبط بمستخدم
            $table->string('path'); // مسار الصورة
            $table->float('score')->nullable(); // نتيجة فحص الحيوية إن وجدت
            $table->boolean('liveness')->nullable(); // true: حقيقي، false: مزيف
            $table->json('meta')->nullable(); // بيانات إضافية
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('face_images');
    }
};