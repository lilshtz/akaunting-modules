<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Chart of Accounts
        Schema::create('double_entry_accounts', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('company_id');
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->string('type', 50); // asset, liability, equity, income, expense
            $table->string('code', 20);
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('opening_balance', 15, 4)->default(0);
            $table->boolean('enabled')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index('company_id');
            $table->index('parent_id');
            $table->index(['company_id', 'type']);
            $table->index(['company_id', 'code']);
            $table->index(['company_id', 'enabled']);
        });

        // Account Defaults (maps system types to COA accounts)
        Schema::create('double_entry_account_defaults', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('company_id');
            $table->string('type', 50); // bank_current, accounts_receivable, etc.
            $table->unsignedBigInteger('account_id');

            $table->index('company_id');
            $table->unique(['company_id', 'type']);
            $table->index('account_id');

            $table->foreign('account_id')
                ->references('id')
                ->on('double_entry_accounts')
                ->cascadeOnDelete();
        });

        // Journal Entries
        Schema::create('double_entry_journals', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('company_id');
            $table->string('number', 50);
            $table->datetime('date');
            $table->text('description')->nullable();
            $table->string('reference')->nullable(); // e.g. "invoice:123"
            $table->string('status', 20)->default('draft'); // draft, posted, voided
            $table->boolean('is_recurring')->default(false);
            $table->string('recurring_frequency', 20)->nullable();
            $table->datetime('next_run_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('company_id');
            $table->index(['company_id', 'number']);
            $table->index(['company_id', 'status']);
            $table->index(['company_id', 'date']);
            $table->index(['company_id', 'is_recurring']);
            $table->index('reference');
        });

        // Journal Lines (debit/credit entries)
        Schema::create('double_entry_journal_lines', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('company_id');
            $table->unsignedBigInteger('journal_id');
            $table->unsignedBigInteger('account_id');
            $table->decimal('debit', 15, 4)->default(0);
            $table->decimal('credit', 15, 4)->default(0);
            $table->text('description')->nullable();
            $table->timestamps();

            $table->index('company_id');
            $table->index('journal_id');
            $table->index('account_id');
            $table->index(['company_id', 'account_id']);

            $table->foreign('journal_id')
                ->references('id')
                ->on('double_entry_journals')
                ->cascadeOnDelete();

            $table->foreign('account_id')
                ->references('id')
                ->on('double_entry_accounts')
                ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('double_entry_journal_lines');
        Schema::dropIfExists('double_entry_journals');
        Schema::dropIfExists('double_entry_account_defaults');
        Schema::dropIfExists('double_entry_accounts');
    }
};
