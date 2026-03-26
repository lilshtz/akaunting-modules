<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payslips', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('payroll_run_id');
            $table->unsignedBigInteger('employee_id');
            $table->decimal('gross', 15, 4)->default(0);
            $table->decimal('total_benefits', 15, 4)->default(0);
            $table->decimal('total_deductions', 15, 4)->default(0);
            $table->decimal('net', 15, 4)->default(0);
            $table->string('pdf_path')->nullable();
            $table->timestamp('emailed_at')->nullable();
            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('companies')->cascadeOnDelete();
            $table->foreign('payroll_run_id')->references('id')->on('payroll_runs')->cascadeOnDelete();
            $table->foreign('employee_id')->references('id')->on('employees')->cascadeOnDelete();
            $table->unique(['payroll_run_id', 'employee_id']);
            $table->index(['company_id', 'employee_id']);
        });

        Schema::create('payslip_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('payslip_id');
            $table->unsignedBigInteger('pay_item_id')->nullable();
            $table->enum('type', ['benefit', 'deduction']);
            $table->string('name');
            $table->decimal('amount', 15, 4)->default(0);
            $table->boolean('is_percentage')->default(false);
            $table->string('percentage_of')->nullable();
            $table->timestamps();

            $table->foreign('payslip_id')->references('id')->on('payslips')->cascadeOnDelete();
            $table->foreign('pay_item_id')->references('id')->on('pay_items')->nullOnDelete();
            $table->index(['payslip_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payslip_items');
        Schema::dropIfExists('payslips');
    }
};
