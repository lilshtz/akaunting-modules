<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('crm_companies')) {
            Schema::create('crm_companies', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->string('name');
            $table->text('address')->nullable();
            $table->string('currency', 3)->nullable();
            $table->string('default_stage', 50)->default('lead');
            $table->timestamps();

            $table->index(['company_id', 'name'], 'idx_e371_516a0742');
        });
        }

        if (!Schema::hasTable('crm_contacts')) {
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

            $table->index(['company_id', 'stage'], 'idx_e371_f50a1294');
            $table->index(['company_id', 'source'], 'idx_e371_3b47c6c9');
            $table->index(['company_id', 'crm_company_id'], 'idx_e371_a3ec16c4');
            $table->index(['company_id', 'email'], 'idx_e371_f851eb43');
        });
        }

        if (!Schema::hasTable('crm_activities')) {
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

            $table->index(['company_id', 'crm_contact_id', 'created_at'], 'idx_e371_d2fcfd63');
            $table->index(['company_id', 'type'], 'idx_e371_ca627f5b');
            $table->index(['company_id', 'scheduled_at'], 'idx_e371_8f2257a3');
        });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('crm_activities');
        Schema::dropIfExists('crm_contacts');
        Schema::dropIfExists('crm_companies');
    }
};
