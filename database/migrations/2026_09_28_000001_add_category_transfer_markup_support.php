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
        Schema::table('branches', function (Blueprint $table) {
            if (! Schema::hasColumn('branches', 'has_custom_category_markup')) {
                $afterColumn = Schema::hasColumn('branches', 'transfer_markup_percentage')
                    ? 'transfer_markup_percentage'
                    : 'store_id';

                $table->boolean('has_custom_category_markup')
                    ->default(false)
                    ->after($afterColumn);
            }
        });

        if (! Schema::hasTable('branch_category_markups')) {
            Schema::create('branch_category_markups', function (Blueprint $table) {
                $table->id();
                $table->foreignId('branch_id')
                    ->constrained('branches')
                    ->cascadeOnDelete();
                $table->foreignId('category_id')
                    ->constrained('categories')
                    ->cascadeOnDelete();
                $table->decimal('transfer_markup_percentage', 5, 2)
                    ->default(0.00);
                $table->timestamps();

                $table->unique(['branch_id', 'category_id'], 'branch_category_markups_unique');
            });
        }

        Schema::table('orders_details', function (Blueprint $table) {
            if (! Schema::hasColumn('orders_details', 'transfer_markup_percentage')) {
                $table->decimal('transfer_markup_percentage', 5, 2)
                    ->nullable()
                    ->after('price');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders_details', function (Blueprint $table) {
            if (Schema::hasColumn('orders_details', 'transfer_markup_percentage')) {
                $table->dropColumn('transfer_markup_percentage');
            }
        });

        Schema::dropIfExists('branch_category_markups');

        Schema::table('branches', function (Blueprint $table) {
            if (Schema::hasColumn('branches', 'has_custom_category_markup')) {
                $table->dropColumn('has_custom_category_markup');
            }
        });
    }
};
