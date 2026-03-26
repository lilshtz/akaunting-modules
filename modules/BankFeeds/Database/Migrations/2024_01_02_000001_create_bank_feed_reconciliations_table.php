<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bank_feed_reconciliations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('bank_account_id');
            $table->date('statement_start_date');
            $table->date('statement_end_date');
            $table->decimal('opening_balance', 15, 4)->default(0);
            $table->decimal('closing_balance', 15, 4)->default(0);
            $table->decimal('reconciled_balance', 15, 4)->default(0);
            $table->decimal('difference', 15, 4)->default(0);
            $table->enum('status', ['in_progress', 'completed'])->default('in_progress');
            $table->json('matched_transaction_ids')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->index('company_id');
            $table->index(['company_id', 'bank_account_id']);
            $table->index('status');
        });

        Schema::table('bank_feed_transactions', function (Blueprint $table) {
            $table->string('duplicate_hash', 64)->nullable()->after('status');
            $table->boolean('is_duplicate')->default(false)->after('duplicate_hash');
            $table->decimal('match_confidence', 5, 2)->nullable()->after('is_duplicate');
            $table->unsignedBigInteger('reconciliation_id')->nullable()->after('match_confidence');

            $table->index('duplicate_hash');
            $table->index('reconciliation_id');
        });
    }

    public function down(): void
    {
        Schema::table('bank_feed_transactions', function (Blueprint $table) {
            $table->dropIndex(['duplicate_hash']);
            $table->dropIndex(['reconciliation_id']);
            $table->dropColumn(['duplicate_hash', 'is_duplicate', 'match_confidence', 'reconciliation_id']);
        });

        Schema::dropIfExists('bank_feed_reconciliations');
    }
};
