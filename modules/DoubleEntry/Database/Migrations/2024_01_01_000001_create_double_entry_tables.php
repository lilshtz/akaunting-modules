<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('double_entry_accounts')) {
            Schema::create('double_entry_accounts', function (Blueprint $table) {
                $table->id();
                $table->unsignedInteger('company_id');
                $table->unsignedBigInteger('parent_id')->nullable();
                $table->string('code', 20);
                $table->string('name');
                $table->string('type', 32);
                $table->string('detail_type')->nullable();
                $table->text('description')->nullable();
                $table->decimal('opening_balance', 15, 4)->default(0);
                $table->boolean('enabled')->default(true);
                $table->unsignedInteger('created_by')->nullable();
                $table->unsignedInteger('updated_by')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->unique(['company_id', 'code']);
                $table->index(['company_id', 'type']);
                $table->foreign('parent_id')->references('id')->on('double_entry_accounts')->nullOnDelete();
            });
        }

        if (!Schema::hasTable('double_entry_account_defaults')) {
            Schema::create('double_entry_account_defaults', function (Blueprint $table) {
                $table->id();
                $table->unsignedInteger('company_id');
                $table->string('key', 64);
                $table->unsignedBigInteger('account_id')->nullable();
                $table->timestamps();

                $table->unique(['company_id', 'key']);
                $table->index('company_id');
                $table->foreign('account_id')->references('id')->on('double_entry_accounts')->nullOnDelete();
            });
        }

        if (!Schema::hasTable('double_entry_journals')) {
            Schema::create('double_entry_journals', function (Blueprint $table) {
                $table->id();
                $table->unsignedInteger('company_id');
                $table->string('number', 50);
                $table->date('date');
                $table->string('status', 16)->default('draft');
                $table->string('reference')->nullable();
                $table->string('source_type')->nullable();
                $table->unsignedInteger('source_id')->nullable();
                $table->text('description')->nullable();
                $table->decimal('total_debit', 15, 4)->default(0);
                $table->decimal('total_credit', 15, 4)->default(0);
                $table->boolean('is_recurring')->default(false);
                $table->string('recurring_frequency', 20)->nullable();
                $table->date('next_run_at')->nullable();
                $table->timestamp('posted_at')->nullable();
                $table->timestamp('voided_at')->nullable();
                $table->unsignedInteger('created_by')->nullable();
                $table->unsignedInteger('updated_by')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->unique(['company_id', 'number']);
                $table->index(['company_id', 'status', 'date']);
                $table->index(['company_id', 'source_type', 'source_id']);
            });
        }

        if (!Schema::hasTable('double_entry_journal_lines')) {
            Schema::create('double_entry_journal_lines', function (Blueprint $table) {
                $table->id();
                $table->unsignedInteger('company_id');
                $table->unsignedBigInteger('journal_id');
                $table->unsignedBigInteger('account_id');
                $table->unsignedInteger('line_number')->default(1);
                $table->string('entry_type', 6);
                $table->text('description')->nullable();
                $table->decimal('amount', 15, 4);
                $table->timestamps();

                $table->index(['company_id', 'account_id']);
                $table->foreign('journal_id')->references('id')->on('double_entry_journals')->cascadeOnDelete();
                $table->foreign('account_id')->references('id')->on('double_entry_accounts')->restrictOnDelete();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('double_entry_journal_lines');
        Schema::dropIfExists('double_entry_journals');
        Schema::dropIfExists('double_entry_account_defaults');
        Schema::dropIfExists('double_entry_accounts');
    }
};
