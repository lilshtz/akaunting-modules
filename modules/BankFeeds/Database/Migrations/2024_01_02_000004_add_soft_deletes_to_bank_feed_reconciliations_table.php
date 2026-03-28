<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('bank_feed_reconciliations') || Schema::hasColumn('bank_feed_reconciliations', 'deleted_at')) {
            return;
        }

        Schema::table('bank_feed_reconciliations', function (Blueprint $table): void {
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('bank_feed_reconciliations') || ! Schema::hasColumn('bank_feed_reconciliations', 'deleted_at')) {
            return;
        }

        Schema::table('bank_feed_reconciliations', function (Blueprint $table): void {
            $table->dropSoftDeletes();
        });
    }
};
