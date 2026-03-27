<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('departments')) {
            Schema::create('departments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->string('name');
            $table->text('description')->nullable();
            $table->unsignedBigInteger('manager_id')->nullable();
            $table->timestamps();


            $table->index('company_id');
        });
        }

        if (!Schema::hasTable('employees')) {
            Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('contact_id');
            $table->unsignedBigInteger('department_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('photo_path')->nullable();
            $table->date('hire_date')->nullable();
            $table->date('birthday')->nullable();
            $table->decimal('salary', 15, 4)->nullable();
            $table->enum('salary_type', ['hourly', 'weekly', 'biweekly', 'monthly', 'yearly'])->nullable();
            $table->string('bank_name')->nullable();
            $table->text('bank_account')->nullable();
            $table->text('bank_routing')->nullable();
            $table->enum('type', ['full_time', 'part_time', 'contractor', 'seasonal'])->default('full_time');
            $table->enum('classification', ['w2', '1099'])->default('w2');
            $table->enum('status', ['active', 'inactive', 'terminated'])->default('active');
            $table->date('terminated_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('department_id')->references('id')->on('departments')->onDelete('set null');

            $table->index('company_id');
            $table->index('department_id');
            $table->index('status');
            $table->index(['company_id', 'status'], 'idx_5fcc_1915fc9e');
        });
        }

        // Add manager FK after employees table exists
        Schema::table('departments', function (Blueprint $table) {
            $table->foreign('manager_id')->references('id')->on('employees')->onDelete('set null');
        });

        if (!Schema::hasTable('employee_documents')) {
            Schema::create('employee_documents', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('employee_id');
            $table->string('name');
            $table->string('file_path');
            $table->string('type', 50)->default('other');
            $table->timestamp('uploaded_at')->useCurrent();
            $table->text('notes')->nullable();

            $table->foreign('employee_id')->references('id')->on('employees')->onDelete('cascade');

            $table->index('employee_id');
        });
        }
    }

    public function down(): void
    {
        Schema::table('departments', function (Blueprint $table) {
            $table->dropForeign(['manager_id']);
        });

        Schema::dropIfExists('employee_documents');
        Schema::dropIfExists('employees');
        Schema::dropIfExists('departments');
    }
};
