<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('receipts')) {
            Schema::create('receipts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->string('image_path');
            $table->string('thumbnail_path')->nullable();
            $table->json('ocr_raw_json')->nullable();
            $table->string('vendor_name')->nullable();
            $table->date('receipt_date')->nullable();
            $table->decimal('amount', 15, 4)->nullable();
            $table->decimal('tax_amount', 15, 4)->nullable();
            $table->string('currency', 3)->nullable();
            $table->unsignedBigInteger('category_id')->nullable();
            $table->enum('status', ['uploaded', 'reviewed', 'processed', 'matched'])->default('uploaded');
            $table->unsignedBigInteger('transaction_id')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('company_id');
            $table->index('status');
            $table->index(['company_id', 'vendor_name', 'amount', 'receipt_date'], 'idx_8e53_52509017');
        });
        }

        if (!Schema::hasTable('receipt_categorization_rules')) {
            Schema::create('receipt_categorization_rules', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->string('vendor_pattern');
            $table->unsignedBigInteger('category_id');
            $table->unsignedBigInteger('account_id')->nullable();
            $table->boolean('enabled')->default(true);
            $table->integer('priority')->default(0);
            $table->timestamps();

            $table->index('company_id');
        });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('receipt_categorization_rules');
        Schema::dropIfExists('receipts');
    }
};
