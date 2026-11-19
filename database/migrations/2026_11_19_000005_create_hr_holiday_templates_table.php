<?php

declare(strict_types=1);

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
        Schema::create('hr_holiday_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150);
            $table->string('type', 30)->default('fixed'); // fixed, hijri
            $table->boolean('is_recurring')->default(true);

            $table->unsignedTinyInteger('day')->nullable();
            $table->unsignedTinyInteger('month')->nullable();
            $table->unsignedSmallInteger('year')->nullable();

            $table->date('from_date')->nullable();
            $table->date('to_date')->nullable();
            $table->unsignedSmallInteger('count_days')->default(1);

            $table->string('status', 30)->default('confirmed'); // tentative, confirmed
            $table->boolean('all_branches')->default(true);
            $table->json('branch_ids')->nullable();
            $table->boolean('active')->default(true);
            $table->text('notes')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('confirmed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('confirmed_at')->nullable();

            $table->softDeletes();
            $table->timestamps();

            $table->index(['type', 'status', 'active'], 'hr_ht_type_status_active_idx');
            $table->index(['year', 'status'], 'hr_ht_year_status_idx');
            $table->index(['from_date', 'to_date'], 'hr_ht_date_range_idx');
            $table->index(['month', 'day'], 'hr_ht_month_day_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hr_holiday_templates');
    }
};
