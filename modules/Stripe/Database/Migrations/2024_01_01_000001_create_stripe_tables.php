<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('stripe_settings')) {
            Schema::create('stripe_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->text('api_key')->nullable();
            $table->text('webhook_secret')->nullable();
            $table->boolean('test_mode')->default(true);
            $table->boolean('enabled')->default(true);
            $table->timestamps();

            $table->index('company_id');
        });
        }

        if (!Schema::hasTable('stripe_payments')) {
            Schema::create('stripe_payments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('document_id');
            $table->string('stripe_payment_intent_id')->nullable();
            $table->string('stripe_charge_id')->nullable();
            $table->string('stripe_session_id')->nullable();
            $table->decimal('amount', 15, 4);
            $table->string('currency', 3);
            $table->enum('status', ['pending', 'succeeded', 'failed', 'refunded'])->default('pending');
            $table->string('refund_id')->nullable();
            $table->timestamps();

            $table->index('company_id');
            $table->index('document_id');
            $table->index('stripe_payment_intent_id');
            $table->index('stripe_session_id');
        });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('stripe_payments');
        Schema::dropIfExists('stripe_settings');
    }
};
