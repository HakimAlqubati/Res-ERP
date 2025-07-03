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
        Schema::create('reseller_sale_paid_amounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reseller_sale_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount', 15, 4); // كافي للمبالغ المالية
            $table->date('paid_at')->nullable();
            $table->text('notes')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reseller_sale_paid_amounts');
    }
};