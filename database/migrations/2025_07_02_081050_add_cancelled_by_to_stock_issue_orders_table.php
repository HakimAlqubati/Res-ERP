<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('stock_issue_orders', function (Blueprint $table) {
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
        });
    }

    public function down()
    {
        Schema::table('stock_issue_orders', function (Blueprint $table) {
            $table->dropForeign(['cancelled_by']);
            $table->dropColumn('cancelled_by');
        });
    }   
};