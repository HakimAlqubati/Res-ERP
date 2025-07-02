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
            $table->timestamp('cancelled_at')->nullable()->after('cancelled_by');
        });
    }

    public function down()
    {
        Schema::table('stock_issue_orders', function (Blueprint $table) {
            $table->dropColumn('cancelled_at');
        });
    }
};