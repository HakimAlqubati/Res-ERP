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
        Schema::create('hr_holiday_occurrences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('holiday_template_id')
                ->nullable()
                ->constrained('hr_holiday_templates')
                ->nullOnDelete();

            $table->string('name', 150);
            $table->string('type', 30)->default('fixed'); // fixed, hijri

            $table->unsignedSmallInteger('year');
            $table->unsignedSmallInteger('hijri_year')->nullable();

            $table->date('expected_from_date')->nullable();
            $table->date('expected_to_date')->nullable();

            $table->date('from_date');
            $table->date('to_date');
            $table->unsignedSmallInteger('count_days')->default(1);

            $table->string('status', 30)->default('tentative'); // tentative, confirmed, cancelled
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

            $table->index(['year', 'status', 'active'], 'hr_ho_year_status_active_idx');
            $table->index(['from_date', 'to_date', 'status'], 'hr_ho_date_range_status_idx');
            $table->index(['holiday_template_id', 'year', 'hijri_year'], 'hr_ho_tpl_year_hijri_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hr_holiday_occurrences');
    }
};
