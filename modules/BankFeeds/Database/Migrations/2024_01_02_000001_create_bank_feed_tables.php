<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bank_feed_imports', function (Blueprint $table): void {
            $table->increments('id');
            $table->unsignedInteger('company_id')->index();
            $table->unsignedInteger('bank_account_id')->nullable()->index();
            $table->string('filename');
            $table->string('original_filename');
            $table->enum('format', ['csv', 'ofx', 'qfx']);
            $table->unsignedInteger('row_count')->default(0);
            $table->enum('status', ['pending', 'processing', 'complete', 'failed'])->default('pending');
            $table->json('column_mapping')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('imported_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('bank_feed_rules', function (Blueprint $table): void {
            $table->increments('id');
            $table->unsignedInteger('company_id')->index();
            $table->string('name', 191);
            $table->enum('field', ['description', 'amount', 'type']);
            $table->enum('operator', ['contains', 'equals', 'starts_with', 'gt', 'lt', 'between']);
            $table->string('value');
            $table->string('value_end')->nullable();
            $table->unsignedInteger('category_id')->nullable()->index();
            $table->boolean('enabled')->default(true);
            $table->unsignedInteger('priority')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('bank_feed_transactions', function (Blueprint $table): void {
            $table->increments('id');
            $table->unsignedInteger('company_id')->index();
            $table->unsignedInteger('import_id');
            $table->unsignedInteger('bank_account_id')->nullable()->index();
            $table->date('date');
            $table->string('description', 500);
            $table->decimal('amount', 15, 4);
            $table->enum('type', ['deposit', 'withdrawal']);
            $table->json('raw_data_json')->nullable();
            $table->unsignedInteger('category_id')->nullable()->index();
            $table->unsignedInteger('matched_journal_id')->nullable()->index();
            $table->enum('status', ['pending', 'categorized', 'matched', 'ignored'])->default('pending');
            $table->string('duplicate_hash', 64)->nullable()->index();
            $table->boolean('is_duplicate')->default(false);
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('import_id')->references('id')->on('bank_feed_imports')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bank_feed_transactions');
        Schema::dropIfExists('bank_feed_rules');
        Schema::dropIfExists('bank_feed_imports');
    }
};
