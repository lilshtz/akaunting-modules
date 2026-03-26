<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('credit_debit_note_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->unique();
            $table->string('cn_prefix', 20)->default('CN-');
            $table->integer('cn_next_number')->default(1);
            $table->string('dn_prefix', 20)->default('DN-');
            $table->integer('dn_next_number')->default(1);
            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
        });

        Schema::create('credit_debit_note_histories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('document_id');
            $table->string('status', 50);
            $table->string('notify', 1)->default('0');
            $table->text('description')->nullable();
            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('document_id')->references('id')->on('documents')->onDelete('cascade');
            $table->index(['document_id', 'status']);
        });

        Schema::create('credit_debit_note_portal_tokens', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('document_id');
            $table->string('token', 64)->unique();
            $table->timestamp('viewed_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('document_id')->references('id')->on('documents')->onDelete('cascade');
            $table->index('token');
        });

        Schema::create('credit_note_applications', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('credit_note_id');
            $table->unsignedBigInteger('invoice_id');
            $table->decimal('amount', 15, 4);
            $table->date('date');
            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('credit_note_id')->references('id')->on('documents')->onDelete('cascade');
            $table->foreign('invoice_id')->references('id')->on('documents')->onDelete('cascade');
            $table->index(['credit_note_id', 'invoice_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('credit_note_applications');
        Schema::dropIfExists('credit_debit_note_portal_tokens');
        Schema::dropIfExists('credit_debit_note_histories');
        Schema::dropIfExists('credit_debit_note_settings');
    }
};
