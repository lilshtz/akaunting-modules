<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('paypal_sync_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->text('client_id')->nullable();
            $table->text('client_secret')->nullable();
            $table->enum('mode', ['sandbox', 'live'])->default('sandbox');
            $table->unsignedBigInteger('bank_account_id')->nullable();
            $table->datetime('last_sync')->nullable();
            $table->boolean('enabled')->default(true);
            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->index('company_id');
        });

        Schema::create('paypal_sync_transactions', function (Blueprint $table) {
            $table->id();
            $table->string('paypal_transaction_id')->unique();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('bank_transaction_id')->nullable();
            $table->decimal('amount', 15, 4);
            $table->string('currency', 3);
            $table->date('date');
            $table->text('description')->nullable();
            $table->string('payer_email')->nullable();
            $table->enum('status', ['pending', 'completed', 'refunded', 'reversed', 'denied'])->default('pending');
            $table->json('raw_json')->nullable();
            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->index('company_id');
            $table->index('paypal_transaction_id');
            $table->index('payer_email');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('paypal_sync_transactions');
        Schema::dropIfExists('paypal_sync_settings');
    }
};
