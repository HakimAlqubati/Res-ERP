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
        Schema::create('app_logs', function (Blueprint $table) {
            $table->id();
            // مستوى الرسالة
            $table->enum('level', ['debug', 'info', 'notice', 'warning', 'error', 'critical', 'alert', 'emergency'])
                ->default('info');

            // اسم الموديول أو الكلاس/المكان
            $table->string('context')->nullable();

            // الرسالة الرئيسية
            $table->text('message');

            // بيانات إضافية (json)
            $table->json('extra')->nullable();

            // المستخدم المرتبط (اختياري)
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();

            // IP أو request id مثلاً
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent')->nullable();

            $table->timestamps();

            // فهرسة للبحث السريع
            $table->index(['level', 'created_at']); 
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('app_logs');
    }
};
