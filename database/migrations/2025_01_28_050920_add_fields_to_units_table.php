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
        return;
        Schema::table('units', function (Blueprint $table) {
            // Add 'parent_unit_id' as a foreign key
            $table->unsignedBigInteger('parent_unit_id')->nullable()->after('id');

            // Add 'conversion_factor' as a decimal column
            $table->decimal('conversion_factor', 10, 4)->nullable()->after('parent_unit_id');

            // Add 'operation' as a string column (e.g., 'multiply' or 'divide')
            $table->enum('operation', ['*', '/'])->after('conversion_factor');

            // Define the foreign key constraint for 'parent_unit_id'
            $table->foreign('parent_unit_id')
                ->references('id')
                ->on('units')
                ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('units', function (Blueprint $table) {
            // Drop the foreign key constraint
            $table->dropForeign(['parent_unit_id']);

            // Drop the columns
            $table->dropColumn('parent_unit_id');
            $table->dropColumn('conversion_factor');
            $table->dropColumn('operation');
        });
    }
};
