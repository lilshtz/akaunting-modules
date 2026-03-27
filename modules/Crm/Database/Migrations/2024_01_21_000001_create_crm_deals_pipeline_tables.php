<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('crm_pipeline_stages')) {
            Schema::create('crm_pipeline_stages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->string('name');
            $table->unsignedInteger('position')->default(0);
            $table->string('color', 20)->default('#0ea5e9');
            $table->boolean('is_won')->default(false);
            $table->boolean('is_lost')->default(false);
            $table->timestamps();

            $table->index(['company_id', 'position'], 'idx_8d78_74baa35a');
        });
        }

        if (!Schema::hasTable('crm_deals')) {
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

            $table->foreign('stage_id')->references('id')->on('crm_pipeline_stages')->onDelete('cascade');
            $table->index(['company_id', 'status'], 'idx_8d78_1915fc9e');
            $table->index(['company_id', 'stage_id'], 'idx_8d78_7eb57f63');
            $table->index(['company_id', 'crm_contact_id'], 'idx_8d78_b6c292c0');
            $table->index(['company_id', 'closed_at'], 'idx_8d78_97a3cda7');
        });
        }

        Schema::table('crm_activities', function (Blueprint $table) {
            $table->index(['company_id', 'crm_deal_id', 'created_at'], 'idx_8d78_d0534431');
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
