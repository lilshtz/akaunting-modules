<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bank_feed_imports', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('bank_account_id');
            $table->string('filename');
            $table->enum('format', ['csv', 'ofx', 'qfx']);
            $table->unsignedInteger('row_count')->default(0);
            $table->enum('status', ['pending', 'processing', 'complete', 'failed'])->default('pending');
            $table->json('column_mapping')->nullable();
            $table->timestamp('imported_at')->nullable();
            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->index('company_id');
            $table->index(['company_id', 'bank_account_id']);
            $table->index('status');
        });

        Schema::create('bank_feed_rules', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->enum('field', ['description', 'vendor', 'amount']);
            $table->enum('operator', ['contains', 'equals', 'starts_with', 'gt', 'lt', 'between']);
            $table->string('value');
            $table->unsignedBigInteger('category_id')->nullable();
            $table->unsignedBigInteger('vendor_id')->nullable();
            $table->boolean('enabled')->default(true);
            $table->integer('priority')->default(0);
            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->index('company_id');
            $table->index(['company_id', 'enabled', 'priority']);
        });

        Schema::create('bank_feed_transactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('import_id');
            $table->unsignedBigInteger('bank_account_id');
            $table->date('date');
            $table->string('description');
            $table->decimal('amount', 15, 4);
            $table->enum('type', ['deposit', 'withdrawal']);
            $table->json('raw_data_json')->nullable();
            $table->unsignedBigInteger('category_id')->nullable();
            $table->unsignedBigInteger('vendor_id')->nullable();
            $table->unsignedBigInteger('matched_transaction_id')->nullable();
            $table->enum('status', ['pending', 'categorized', 'matched', 'ignored'])->default('pending');
            $table->timestamps();

            $table->foreign('import_id')->references('id')->on('bank_feed_imports')->onDelete('cascade');
            $table->index('import_id');
            $table->index('bank_account_id');
            $table->index('status');
            $table->index(['bank_account_id', 'date', 'amount']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bank_feed_transactions');
        Schema::dropIfExists('bank_feed_rules');
        Schema::dropIfExists('bank_feed_imports');
    }
};
