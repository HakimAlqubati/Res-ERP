<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hr_advance_wages', function (Blueprint $table) {
            $table->id();

            $table->foreignId('employee_id')
                ->constrained('hr_employees')
                ->cascadeOnDelete();

            $table->foreignId('branch_id')
                ->constrained('branches')
                ->cascadeOnDelete();

            $table->unsignedSmallInteger('year');
            $table->unsignedTinyInteger('month');

            $table->decimal('amount', 12, 2);

            // pending = صُرف ولم يُخصم بعد من الراتب
            // settled = خُصم من الراتب
            // cancelled = ملغى
            $table->enum('status', ['pending', 'settled', 'cancelled'])->default('pending');

            $table->string('reason')->nullable();   // سبب الأجر المقدم
            $table->text('notes')->nullable();

            // ربط بالـ Payroll الذي سوَّاه (يُملأ وقت بناء الرواتب)
            $table->foreignId('settled_payroll_id')
                ->nullable()
                ->constrained('hr_payrolls')
                ->nullOnDelete();

            $table->timestamp('settled_at')->nullable();

            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignId('approved_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('approved_at')->nullable();

            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hr_advance_wages');
    }
};
