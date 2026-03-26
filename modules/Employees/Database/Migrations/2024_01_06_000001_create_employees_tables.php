<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('departments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->string('name');
            $table->text('description')->nullable();
            $table->unsignedBigInteger('manager_id')->nullable();
            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');

            $table->index('company_id');
        });

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

            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('contact_id')->references('id')->on('contacts')->onDelete('cascade');
            $table->foreign('department_id')->references('id')->on('departments')->onDelete('set null');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');

            $table->index('company_id');
            $table->index('department_id');
            $table->index('status');
            $table->index(['company_id', 'status']);
        });

        // Add manager FK after employees table exists
        Schema::table('departments', function (Blueprint $table) {
            $table->foreign('manager_id')->references('id')->on('employees')->onDelete('set null');
        });

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
