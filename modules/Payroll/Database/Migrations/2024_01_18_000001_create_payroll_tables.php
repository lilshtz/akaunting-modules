<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('pay_items')) {
            Schema::create('pay_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->enum('type', ['benefit', 'deduction']);
            $table->string('name');
            $table->decimal('default_amount', 15, 4)->nullable();
            $table->boolean('is_percentage')->default(false);
            $table->boolean('enabled')->default(true);
            $table->timestamps();

            $table->index(['company_id', 'type'], 'idx_8788_ca627f5b');
        });
        }

        if (!Schema::hasTable('pay_calendars')) {
            Schema::create('pay_calendars', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->string('name');
            $table->enum('frequency', ['weekly', 'biweekly', 'monthly', 'custom']);
            $table->date('start_date');
            $table->date('next_run_date');
            $table->boolean('enabled')->default(true);
            $table->timestamps();

            $table->index(['company_id', 'enabled'], 'idx_8788_8272d36f');
        });
        }

        if (!Schema::hasTable('pay_calendar_employees')) {
            Schema::create('pay_calendar_employees', function (Blueprint $table) {
            $table->unsignedBigInteger('pay_calendar_id');
            $table->unsignedBigInteger('employee_id');

            $table->primary(['pay_calendar_id', 'employee_id']);
            $table->foreign('pay_calendar_id')->references('id')->on('pay_calendars')->cascadeOnDelete();
            $table->foreign('employee_id')->references('id')->on('employees')->cascadeOnDelete();
        });
        }

        if (!Schema::hasTable('payroll_runs')) {
            Schema::create('payroll_runs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('pay_calendar_id');
            $table->date('period_start');
            $table->date('period_end');
            $table->enum('status', ['draft', 'review', 'approved', 'processed', 'completed'])->default('draft');
            $table->decimal('total_gross', 15, 4)->default(0);
            $table->decimal('total_deductions', 15, 4)->default(0);
            $table->decimal('total_net', 15, 4)->default(0);
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            $table->foreign('pay_calendar_id')->references('id')->on('pay_calendars')->cascadeOnDelete();
            $table->index(['company_id', 'status'], 'idx_8788_1915fc9e');
        });
        }

        if (!Schema::hasTable('payroll_run_employees')) {
            Schema::create('payroll_run_employees', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('payroll_run_id');
            $table->unsignedBigInteger('employee_id');
            $table->decimal('gross_amount', 15, 4)->default(0);
            $table->decimal('benefit_amount', 15, 4)->default(0);
            $table->decimal('deduction_amount', 15, 4)->default(0);
            $table->decimal('net_amount', 15, 4)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('payroll_run_id')->references('id')->on('payroll_runs')->cascadeOnDelete();
            $table->foreign('employee_id')->references('id')->on('employees')->cascadeOnDelete();
            $table->unique(['payroll_run_id', 'employee_id']);
        });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_run_employees');
        Schema::dropIfExists('payroll_runs');
        Schema::dropIfExists('pay_calendar_employees');
        Schema::dropIfExists('pay_calendars');
        Schema::dropIfExists('pay_items');
    }
};
