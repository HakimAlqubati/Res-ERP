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
        Schema::table('categories', function (Blueprint $table) {
            // Add the parent_id column as a nullable foreign key
            $table->foreignId('parent_id')
                ->nullable()
                ->after('description') // Placed after 'description' for better organization
                ->constrained('categories') // References the 'categories' table itself
                ->onDelete('set null'); // If the parent is deleted, set parent_id to NULL
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            // Drop the foreign key constraint first
            $table->dropForeign(['parent_id']);
            // Drop the column
            $table->dropColumn('parent_id');
        });
    }
};
