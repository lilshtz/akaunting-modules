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
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->string('code', 50);
            $table->string('name');
            $table->enum('type', ['asset', 'liability', 'equity', 'income', 'expense']);
            $table->text('description')->nullable();
            $table->decimal('opening_balance', 15, 4)->default(0);
            $table->boolean('enabled')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('parent_id')->references('id')->on('double_entry_accounts')->onDelete('set null');

            $table->index('company_id');
            $table->index('type');
            $table->unique(['company_id', 'code']);
        });
        }

        if (!Schema::hasTable('double_entry_account_defaults')) {
            Schema::create('double_entry_account_defaults', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->string('type', 50);
            $table->unsignedBigInteger('account_id');
            $table->timestamps();


            $table->index('company_id');
        });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('double_entry_account_defaults');
        Schema::dropIfExists('double_entry_accounts');
    }
};
