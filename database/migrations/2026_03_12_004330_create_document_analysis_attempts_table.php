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
        Schema::create('document_analysis_attempts', function (Blueprint $table) {
            $table->id();

            // Allows linking the attempt to a specific GRN or Purchase Invoice
            // Using nullableMorphs because the file might be uploaded on a "Create" page
            // where the parent record doesn't exist yet.
            $table->nullableMorphs('documentable', 'doc_analysis_morph_index');
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();

            $table->string('provider')->default('textract');
            $table->string('file_name')->nullable();

            // Status of the attempt: pending, success, failed
            $table->string('status')->default('pending');

            $table->json('payload')->nullable(); // Raw response from provider
            $table->json('mapped_data')->nullable(); // Extracted/Usable data representation
            $table->text('error_message')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('document_analysis_attempts');
    }
};
