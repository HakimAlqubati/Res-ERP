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
        Schema::create('hr_administrations', function (Blueprint $table) {
            $table->id();
            $table->string('name');  
            $table->foreignId('manager_id')->nullable()->constrained('hr_employees')->onDelete('set null'); 
            $table->text('description')->nullable(); 
            $table->string('status')->default('active'); 
            $table->date('start_date')->nullable(); 
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hr_administrations');
    }
};
