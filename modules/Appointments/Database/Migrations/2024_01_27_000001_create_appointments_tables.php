<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('appointments')) {
            Schema::create('appointments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('contact_id')->nullable();
            $table->unsignedBigInteger('user_id');
            $table->date('date');
            $table->time('start_time');
            $table->time('end_time');
            $table->string('location')->nullable();
            $table->enum('status', ['scheduled', 'completed', 'cancelled', 'no_show'])->default('scheduled');
            $table->text('notes')->nullable();
            $table->boolean('reminder_sent')->default(false);
            $table->timestamps();

            $table->index(['company_id', 'date'], 'idx_6eb1_f2367604');
            $table->index(['company_id', 'status'], 'idx_6eb1_1915fc9e');
        });
        }

        if (!Schema::hasTable('appointment_forms')) {
            Schema::create('appointment_forms', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->string('name');
            $table->json('fields_json')->nullable();
            $table->string('public_link')->unique();
            $table->boolean('enabled')->default(true);
            $table->timestamps();

            $table->index(['company_id', 'enabled'], 'idx_6eb1_8272d36f');
        });
        }

        if (!Schema::hasTable('leave_requests')) {
            Schema::create('leave_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('employee_id');
            $table->unsignedBigInteger('approver_id')->nullable();
            $table->enum('type', ['vacation', 'sick', 'personal', 'other'])->default('vacation');
            $table->date('start_date');
            $table->date('end_date');
            $table->decimal('days', 8, 2)->default(0);
            $table->enum('status', ['pending', 'approved', 'refused'])->default('pending');
            $table->text('reason')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('refused_at')->nullable();
            $table->text('refusal_reason')->nullable();
            $table->timestamps();

            $table->foreign('employee_id')->references('id')->on('employees')->cascadeOnDelete();
            $table->index(['company_id', 'status'], 'idx_6eb1_1915fc9e');
            $table->index(['company_id', 'employee_id', 'type'], 'idx_6eb1_fc487f84');
        });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('leave_requests');
        Schema::dropIfExists('appointment_forms');
        Schema::dropIfExists('appointments');
    }
};
