<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('crm_companies', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->string('name');
            $table->text('address')->nullable();
            $table->string('currency', 3)->nullable();
            $table->string('default_stage', 50)->default('lead');
            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->index(['company_id', 'name']);
        });

        Schema::create('crm_contacts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('crm_company_id')->nullable();
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->enum('source', ['web', 'referral', 'email', 'cold', 'phone', 'other'])->default('other');
            $table->enum('stage', ['lead', 'subscriber', 'opportunity', 'customer'])->default('lead');
            $table->unsignedBigInteger('owner_user_id')->nullable();
            $table->unsignedBigInteger('akaunting_contact_id')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('crm_company_id')->references('id')->on('crm_companies')->onDelete('set null');
            $table->foreign('owner_user_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign('akaunting_contact_id')->references('id')->on('contacts')->onDelete('set null');
            $table->index(['company_id', 'stage']);
            $table->index(['company_id', 'source']);
            $table->index(['company_id', 'crm_company_id']);
            $table->index(['company_id', 'email']);
        });

        Schema::create('crm_activities', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('crm_contact_id')->nullable();
            $table->unsignedBigInteger('crm_deal_id')->nullable();
            $table->enum('type', ['call', 'meeting', 'email', 'note', 'task']);
            $table->text('description');
            $table->dateTime('scheduled_at')->nullable();
            $table->dateTime('completed_at')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('crm_contact_id')->references('id')->on('crm_contacts')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
            $table->index(['company_id', 'crm_contact_id', 'created_at']);
            $table->index(['company_id', 'type']);
            $table->index(['company_id', 'scheduled_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crm_activities');
        Schema::dropIfExists('crm_contacts');
        Schema::dropIfExists('crm_companies');
    }
};
