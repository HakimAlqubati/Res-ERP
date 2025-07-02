<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // جدول GoodsReceivedNotes
        Schema::table('goods_received_notes', function (Blueprint $table) {
            $table->boolean('cancelled')->default(false)->after('status');
        });

        // جدول PurchaseInvoices
        Schema::table('purchase_invoices', function (Blueprint $table) {
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete()->after('created_by');
        });
    }

    public function down(): void
    {
        Schema::table('goods_received_notes', function (Blueprint $table) {
            $table->dropColumn('cancelled');
        });

        Schema::table('purchase_invoices', function (Blueprint $table) {
            $table->dropForeign(['cancelled_by']);
            $table->dropColumn('cancelled_by');
        });
    }
};