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
        Schema::create('locations', function (Blueprint $table) {
            $table->id();
            $table->string('address')->nullable();
            $table->bigInteger('district_id')->nullable();
            $table->bigInteger('city_id')->nullable();
            $table->bigInteger('country_id')->nullable();
            $table->string('postal_code')->nullable();
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 10, 8)->nullable();
            
            // Polymorphic columns
            $table->unsignedBigInteger('locationable_id'); // ID of the related model (e.g., User, Store, Office)
            $table->string('locationable_type'); // Class name of the related model (e.g., App\Models\User)
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('locations');
    }
};
