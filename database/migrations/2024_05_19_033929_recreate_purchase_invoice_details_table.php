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
        Schema::dropIfExists('purchase_invoice_details');
        Schema::create('purchase_invoice_details', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('purchase_invoice_id')->unsigned();
            $table->bigInteger('product_id');
            $table->bigInteger('unit_id')->nullable();
            $table->integer('quantity');
            $table->decimal('price', 8, 2)->nullable();
            $table->softDeletes();
            $table->timestamps();

            // Add foreign key constraint
            $table->foreign('purchase_invoice_id')
                ->references('id')->on('purchase_invoices')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('purchase_invoice_details');
    }
};
