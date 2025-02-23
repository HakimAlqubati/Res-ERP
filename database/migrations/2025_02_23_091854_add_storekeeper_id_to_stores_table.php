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
        Schema::table('stores', function (Blueprint $table) {
                 // Add the storekeeper_id column after default_store
                 $table->unsignedBigInteger('storekeeper_id')->nullable()->after('default_store');

                 // Add a foreign key constraint (optional)
                 $table->foreign('storekeeper_id')
                       ->references('id')
                       ->on('users') // Assuming storekeepers are stored in the users table
                       ->onDelete('set null'); // Set storekeeper_id to null if the user is deleted
             
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stores', function (Blueprint $table) {
            $table->dropForeign(['storekeeper_id']);

            // Drop the storekeeper_id column
            $table->dropColumn('storekeeper_id');
        });
    }
};
