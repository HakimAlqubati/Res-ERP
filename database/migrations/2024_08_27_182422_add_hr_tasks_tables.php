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
        Schema::create('hr_task_statuses', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('active')->default(1);
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('hr_tasks', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->bigInteger('assigned_to')->nullable();
            $table->bigInteger('status_id');
            $table->bigInteger('created_by');
            $table->bigInteger('updated_by');
            $table->timestamp('due_date')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('hr_task_comments', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('task_id');
            $table->bigInteger('user_id');
            $table->text('comment');
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('hr_task_attachments', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('task_id');
            $table->string('file_name');
            $table->string('file_path');
            $table->bigInteger('created_by');
            $table->bigInteger('updated_by');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hr_task_statuses');
        Schema::dropIfExists('hr_tasks');
        Schema::dropIfExists('hr_task_comments');
        Schema::dropIfExists('hr_task_attachments');

    }
};
