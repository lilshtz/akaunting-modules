<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('report_schedules', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('company_id');
            $table->string('report_type', 32);
            $table->string('frequency', 16);
            $table->dateTime('next_run')->nullable();
            $table->json('recipients_json')->nullable();
            $table->string('format', 16)->default('pdf');
            $table->string('date_range_type', 32)->default('previous_month');
            $table->date('custom_date_from')->nullable();
            $table->date('custom_date_to')->nullable();
            $table->string('webhook_url')->nullable();
            $table->boolean('enabled')->default(true);
            $table->timestamps();

            $table->index(['company_id', 'enabled', 'next_run'], 'report_schedules_company_enabled_next_run_idx');
        });

        Schema::create('report_schedule_runs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('schedule_id');
            $table->dateTime('ran_at');
            $table->string('file_path')->nullable();
            $table->string('status', 16)->default('failed');
            $table->text('error_message')->nullable();
            $table->dateTime('emailed_at')->nullable();
            $table->timestamps();

            $table->foreign('schedule_id')->references('id')->on('report_schedules')->onDelete('cascade');
            $table->index(['schedule_id', 'ran_at'], 'report_schedule_runs_schedule_ran_at_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('report_schedule_runs');
        Schema::dropIfExists('report_schedules');
    }
};
