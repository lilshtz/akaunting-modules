<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('budgets')) {
            Schema::create('budgets', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->string('name');
            $table->string('period_type', 20)->default('annual');
            $table->string('scenario', 30)->nullable();
            $table->date('period_start');
            $table->date('period_end');
            $table->string('status', 20)->default('draft');
            $table->timestamps();

            $table->index(['company_id', 'status'], 'idx_3a66_1915fc9e');
            $table->index(['company_id', 'period_start', 'period_end'], 'idx_3a66_df9b3537');
        });
        }

        if (!Schema::hasTable('budget_lines')) {
            Schema::create('budget_lines', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('budget_id');
            $table->unsignedBigInteger('account_id');
            $table->decimal('amount', 15, 4)->default(0);
            $table->timestamps();

            $table->foreign('budget_id')->references('id')->on('budgets')->onDelete('cascade');
            $table->unique(['budget_id', 'account_id']);
            $table->index(['account_id'], 'idx_3a66_9e8d6204');
        });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('budget_lines');
        Schema::dropIfExists('budgets');
    }
};
