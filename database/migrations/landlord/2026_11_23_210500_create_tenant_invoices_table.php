<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function getConnection(): ?string
    {
        return config('multitenancy.landlord_database_connection_name', 'landlord');
    }

    public function up(): void
    {
        Schema::connection($this->getConnection())->create('tenant_invoices', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')
                ->index()
                ->constrained('tenants')
                ->restrictOnDelete();
            $table->string('invoice_number')->unique();
            $table->decimal('amount', 10, 2);
            $table->date('invoice_date');
            $table->date('due_date')->index();
            $table->string('status')->default('unpaid')->index();
            $table->timestamp('paid_at')->nullable();
            $table->unsignedSmallInteger('warning_days')->default(10);
            $table->text('notes')->nullable();
            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamps();

            $table->index(['status', 'due_date']);
        });
    }

    public function down(): void
    {
        Schema::connection($this->getConnection())->dropIfExists('tenant_invoices');
    }
};
