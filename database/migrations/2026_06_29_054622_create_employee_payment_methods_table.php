<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('hr_employee_payment_method', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->boolean('active')->default(true);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });

        DB::table('hr_employee_payment_method')->insert([
            ['name' => 'Bank Account', 'active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Touch \'n Go Wallet', 'active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Cash', 'active' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hr_employee_payment_method');
    }
};
