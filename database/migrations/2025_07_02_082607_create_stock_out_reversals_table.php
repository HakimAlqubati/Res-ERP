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
        Schema::create('stock_out_reversals', function (Blueprint $table) {
            $table->id();
            $table->string('reversed_type'); // مثل: App\Models\Order
            $table->unsignedBigInteger('reversed_id');
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->string('reason')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['reversed_type', 'reversed_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_out_reversals');
    }
};