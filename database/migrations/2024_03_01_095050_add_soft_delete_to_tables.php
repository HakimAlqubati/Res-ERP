<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('tables', function (Blueprint $table) {
            Schema::table('products', function (Blueprint $table) {
                $table->softDeletes();
            });

            Schema::table('categories', function (Blueprint $table) {
                $table->softDeletes();
            });

            Schema::table('units', function (Blueprint $table) {
                $table->softDeletes();
            });

            Schema::table('unit_prices', function (Blueprint $table) {
                $table->softDeletes();
            });

            Schema::table('branches', function (Blueprint $table) {
                $table->softDeletes();
            });

            Schema::table('users', function (Blueprint $table) {
                $table->softDeletes();
            });
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('tables', function (Blueprint $table) {
            Schema::table('products', function (Blueprint $table) {
                $table->dropSoftDeletes();
            });

            Schema::table('categories', function (Blueprint $table) {
                $table->dropSoftDeletes();
            });

            Schema::table('units', function (Blueprint $table) {
                $table->dropSoftDeletes();
            });

            Schema::table('unit_prices', function (Blueprint $table) {
                $table->dropSoftDeletes();
            });

            Schema::table('branches', function (Blueprint $table) {
                $table->dropSoftDeletes();
            });

            Schema::table('users', function (Blueprint $table) {
                $table->dropSoftDeletes();
            });
        });
    }
};
