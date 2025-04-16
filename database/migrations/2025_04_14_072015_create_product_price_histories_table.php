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
        Schema::create('product_price_histories', function (Blueprint $table) {
            $table->id();
            
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_item_id')->nullable()->constrained('product_items')->nullOnDelete();
            $table->foreignId('unit_id')->nullable()->constrained()->nullOnDelete();

            $table->decimal('old_price', 12, 4)->nullable();
            $table->decimal('new_price', 12, 4);

            $table->nullableMorphs('source'); // source_type + source_id
            $table->text('note')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_price_histories');
    }
};
