<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('double_entry_accounts', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('company_id')->index();
            $table->unsignedInteger('parent_id')->nullable()->index();
            $table->string('code', 50);
            $table->string('name', 191);
            $table->enum('type', ['asset', 'liability', 'equity', 'income', 'expense']);
            $table->text('description')->nullable();
            $table->decimal('opening_balance', 15, 4)->default(0);
            $table->boolean('enabled')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['company_id', 'code']);
            $table->foreign('parent_id')->references('id')->on('double_entry_accounts')->nullOnDelete();
        });

        Schema::create('double_entry_journals', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('company_id')->index();
            $table->date('date');
            $table->string('reference', 100)->nullable();
            $table->text('description')->nullable();
            $table->enum('basis', ['accrual', 'cash'])->default('accrual');
            $table->enum('status', ['draft', 'posted'])->default('draft');
            $table->string('documentable_type')->nullable();
            $table->unsignedInteger('documentable_id')->nullable();
            $table->enum('recurring_frequency', ['weekly', 'monthly', 'quarterly', 'yearly'])->nullable();
            $table->date('next_recurring_date')->nullable();
            $table->unsignedInteger('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['documentable_type', 'documentable_id'], 'double_entry_journals_documentable_index');
        });

        Schema::create('double_entry_journal_lines', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('journal_id');
            $table->unsignedInteger('account_id')->index();
            $table->decimal('debit', 15, 4)->default(0);
            $table->decimal('credit', 15, 4)->default(0);
            $table->text('description')->nullable();

            $table->foreign('journal_id')->references('id')->on('double_entry_journals')->cascadeOnDelete();
        });

        Schema::create('double_entry_account_defaults', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('company_id')->index();
            $table->string('type', 50);
            $table->unsignedInteger('account_id')->index();
            $table->timestamps();

            $table->unique(['company_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('double_entry_account_defaults');
        Schema::dropIfExists('double_entry_journal_lines');
        Schema::dropIfExists('double_entry_journals');
        Schema::dropIfExists('double_entry_accounts');
    }
};
