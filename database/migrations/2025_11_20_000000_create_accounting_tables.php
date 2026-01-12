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
        Schema::create('acc_currencies', function (Blueprint $table) {
            $table->id();
            $table->string('currency_code'); // USD, SAR
            $table->string('currency_name'); // Saudi Riyal
            $table->string('symbol'); // ﷼
            $table->boolean('is_base')->default(false);
            $table->decimal('exchange_rate', 12, 6)->default(1.000000); // سعر الصرف مقابل العملة الأساسية
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('acc_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('account_code');
            $table->string('account_name');
            $table->enum('account_type', ['asset', 'liability', 'equity', 'revenue', 'expense']);
            $table->boolean('is_parent')->default(false);
            $table->foreignId('parent_id')->nullable()->constrained('acc_accounts')->nullOnDelete();
            $table->boolean('is_active')->default(true);
            $table->boolean('allow_manual_entries')->default(true);
            $table->foreignId('currency_id')->nullable()->constrained('acc_currencies')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('acc_journal_entries', function (Blueprint $table) {
            $table->id();
            $table->date('entry_date');
            $table->string('reference_number');
            $table->string('reference_type'); // Invoice, POS, Purchase, Manual...
            $table->text('description')->nullable();
            $table->integer('branch_id')->nullable(); // Optional
            $table->enum('status', ['draft', 'posted'])->default('draft');
            $table->foreignId('currency_id')->nullable()->constrained('acc_currencies')->nullOnDelete();
            $table->decimal('exchange_rate', 18, 6);
            $table->string('entry_number')->unique();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('acc_journal_entry_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('journal_entry_id')->constrained('acc_journal_entries')->cascadeOnDelete();
            $table->foreignId('account_id')->constrained('acc_accounts')->cascadeOnDelete();
            $table->decimal('debit', 20, 4)->default(0);
            $table->decimal('credit', 20, 4)->default(0);
            $table->decimal('debit_foreign', 20, 4)->default(0);
            $table->decimal('credit_foreign', 20, 4)->default(0);
            $table->integer('cost_center_id')->nullable(); // Optional
            $table->integer('branch_id')->nullable(); // Optional
            $table->string('line_description')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('acc_journal_entry_lines');
        Schema::dropIfExists('acc_journal_entries');
        Schema::dropIfExists('acc_accounts');
        Schema::dropIfExists('acc_currencies');
    }
};
