<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('bank_feed_reconciliations')) {
            return;
        }

        Schema::create('bank_feed_reconciliations', function (Blueprint $table): void {
            $table->increments('id');
            $table->unsignedInteger('company_id')->index();
            $table->unsignedInteger('bank_account_id')->index();
            $table->date('period_start');
            $table->date('period_end');
            $table->decimal('opening_balance', 15, 4)->default(0);
            $table->decimal('closing_balance', 15, 4)->default(0);
            $table->enum('status', ['draft', 'completed'])->default('draft');
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['company_id', 'bank_account_id', 'period_start', 'period_end'], 'bank_feed_reconciliations_period_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bank_feed_reconciliations');
    }
};
