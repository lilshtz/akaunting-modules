<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('crm_pipeline_stages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->string('name');
            $table->unsignedInteger('position')->default(0);
            $table->string('color', 20)->default('#0ea5e9');
            $table->boolean('is_won')->default(false);
            $table->boolean('is_lost')->default(false);
            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->index(['company_id', 'position']);
        });

        Schema::create('crm_deals', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('crm_contact_id');
            $table->string('name');
            $table->decimal('value', 15, 2)->default(0);
            $table->unsignedBigInteger('stage_id');
            $table->date('expected_close')->nullable();
            $table->enum('status', ['open', 'won', 'lost', 'deleted'])->default('open');
            $table->unsignedBigInteger('invoice_id')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('crm_contact_id')->references('id')->on('crm_contacts')->onDelete('cascade');
            $table->foreign('stage_id')->references('id')->on('crm_pipeline_stages')->onDelete('cascade');
            $table->foreign('invoice_id')->references('id')->on('documents')->onDelete('set null');
            $table->index(['company_id', 'status']);
            $table->index(['company_id', 'stage_id']);
            $table->index(['company_id', 'crm_contact_id']);
            $table->index(['company_id', 'closed_at']);
        });

        Schema::table('crm_activities', function (Blueprint $table) {
            $table->foreign('crm_deal_id')->references('id')->on('crm_deals')->onDelete('cascade');
            $table->index(['company_id', 'crm_deal_id', 'created_at']);
        });

        $this->seedDefaultStages();
    }

    public function down(): void
    {
        Schema::table('crm_activities', function (Blueprint $table) {
            $table->dropForeign(['crm_deal_id']);
            $table->dropIndex(['company_id', 'crm_deal_id', 'created_at']);
        });

        Schema::dropIfExists('crm_deals');
        Schema::dropIfExists('crm_pipeline_stages');
    }

    protected function seedDefaultStages(): void
    {
        $companies = DB::table('companies')->pluck('id');
        $now = now();
        $defaults = [
            ['name' => 'Lead', 'color' => '#64748b', 'is_won' => false, 'is_lost' => false],
            ['name' => 'Qualified', 'color' => '#0ea5e9', 'is_won' => false, 'is_lost' => false],
            ['name' => 'Proposal', 'color' => '#8b5cf6', 'is_won' => false, 'is_lost' => false],
            ['name' => 'Negotiation', 'color' => '#f59e0b', 'is_won' => false, 'is_lost' => false],
            ['name' => 'Won', 'color' => '#10b981', 'is_won' => true, 'is_lost' => false],
            ['name' => 'Lost', 'color' => '#ef4444', 'is_won' => false, 'is_lost' => true],
        ];

        foreach ($companies as $companyId) {
            foreach ($defaults as $position => $stage) {
                DB::table('crm_pipeline_stages')->insert([
                    'company_id' => $companyId,
                    'name' => $stage['name'],
                    'position' => $position + 1,
                    'color' => $stage['color'],
                    'is_won' => $stage['is_won'],
                    'is_lost' => $stage['is_lost'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }
};
