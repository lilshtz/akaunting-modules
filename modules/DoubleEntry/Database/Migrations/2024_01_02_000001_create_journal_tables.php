<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('double_entry_journals', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->date('date');
            $table->string('reference', 100)->nullable();
            $table->text('description')->nullable();
            $table->enum('basis', ['accrual', 'cash'])->default('accrual');
            $table->enum('status', ['draft', 'posted'])->default('posted');
            $table->string('documentable_type', 255)->nullable();
            $table->unsignedBigInteger('documentable_id')->nullable();
            $table->enum('recurring_frequency', ['weekly', 'monthly', 'quarterly', 'yearly'])->nullable();
            $table->date('next_recurring_date')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('company_id');
            $table->index(['documentable_type', 'documentable_id']);
            $table->index('date');
            $table->index('status');
            $table->index('next_recurring_date');
            $table->foreign('company_id')->references('id')->on('companies')->cascadeOnDelete();
        });

        Schema::create('double_entry_journal_lines', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('journal_id');
            $table->unsignedBigInteger('account_id');
            $table->decimal('debit', 15, 4)->default(0);
            $table->decimal('credit', 15, 4)->default(0);
            $table->text('description')->nullable();

            $table->index('journal_id');
            $table->index('account_id');
            $table->foreign('journal_id')->references('id')->on('double_entry_journals')->cascadeOnDelete();
            $table->foreign('account_id')->references('id')->on('double_entry_accounts')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('double_entry_journal_lines');
        Schema::dropIfExists('double_entry_journals');
    }
};
