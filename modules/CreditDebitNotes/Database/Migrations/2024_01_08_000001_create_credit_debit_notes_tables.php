<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('credit_debit_note_settings')) {
            Schema::create('credit_debit_note_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->unique();
            $table->string('cn_prefix', 20)->default('CN-');
            $table->integer('cn_next_number')->default(1);
            $table->string('dn_prefix', 20)->default('DN-');
            $table->integer('dn_next_number')->default(1);
            $table->timestamps();

        });
        }

        if (!Schema::hasTable('credit_debit_note_histories')) {
            Schema::create('credit_debit_note_histories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('document_id');
            $table->string('status', 50);
            $table->string('notify', 1)->default('0');
            $table->text('description')->nullable();
            $table->timestamps();

            $table->index(['document_id', 'status'], 'idx_19c1_7b73ea75');
        });
        }

        if (!Schema::hasTable('credit_debit_note_portal_tokens')) {
            Schema::create('credit_debit_note_portal_tokens', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('document_id');
            $table->string('token', 64)->unique();
            $table->timestamp('viewed_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->index('token');
        });
        }

        if (!Schema::hasTable('credit_note_applications')) {
            Schema::create('credit_note_applications', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('credit_note_id');
            $table->unsignedBigInteger('invoice_id');
            $table->decimal('amount', 15, 4);
            $table->date('date');
            $table->timestamps();

            $table->index(['credit_note_id', 'invoice_id'], 'idx_19c1_0738992e');
        });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('credit_note_applications');
        Schema::dropIfExists('credit_debit_note_portal_tokens');
        Schema::dropIfExists('credit_debit_note_histories');
        Schema::dropIfExists('credit_debit_note_settings');
    }
};
