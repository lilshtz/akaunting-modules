<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('project_timesheets')) {
            Schema::create('project_timesheets', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('task_id');
            $table->unsignedBigInteger('user_id');
            $table->dateTime('started_at');
            $table->dateTime('ended_at')->nullable();
            $table->decimal('hours', 8, 2)->nullable();
            $table->boolean('billable')->default(true);
            $table->text('description')->nullable();
            $table->timestamps();

            $table->foreign('task_id')->references('id')->on('project_tasks')->onDelete('cascade');
            $table->index(['task_id', 'started_at'], 'idx_0a6f_b7201d29');
            $table->index(['user_id', 'ended_at'], 'idx_0a6f_37db606f');
        });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('project_timesheets');
    }
};
