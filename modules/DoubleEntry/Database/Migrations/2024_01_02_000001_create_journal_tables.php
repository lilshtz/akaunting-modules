<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('double_entry_journals')) {
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
                $table->unsignedBigInteger('created_by')->nullable();
                $table->string('recurring_frequency')->nullable();
                $table->date('next_recurring_date')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->index('company_id');
                $table->index(['documentable_type', 'documentable_id'], 'idx_d21c_c9b72be9');
                $table->index('date');
                $table->index('status');
                $table->index('next_recurring_date');
            });
        }

        if (!Schema::hasTable('double_entry_journal_lines')) {
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
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('double_entry_journal_lines');
        Schema::dropIfExists('double_entry_journals');
    }
};
