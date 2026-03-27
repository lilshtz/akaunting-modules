<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('expense_claim_categories')) {
            Schema::create('expense_claim_categories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->string('name');
            $table->string('color', 20)->nullable();
            $table->boolean('enabled')->default(true);
            $table->timestamps();

            $table->unique(['company_id', 'name']);
            $table->index(['company_id', 'enabled'], 'idx_41eb_8272d36f');
        });
        }

        if (!Schema::hasTable('expense_claims')) {
            Schema::create('expense_claims', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('employee_id');
            $table->unsignedBigInteger('approver_id')->nullable();
            $table->unsignedBigInteger('reimbursement_document_id')->nullable();
            $table->unsignedBigInteger('reimbursement_transaction_id')->nullable();
            $table->string('status', 30)->default('draft');
            $table->string('claim_number', 50)->nullable();
            $table->text('description')->nullable();
            $table->decimal('total', 15, 4)->default(0);
            $table->decimal('reimbursable_total', 15, 4)->default(0);
            $table->date('due_date')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('refused_at')->nullable();
            $table->text('refusal_reason')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->foreign('employee_id')->references('id')->on('employees')->onDelete('cascade');

            $table->index(['company_id', 'status'], 'idx_41eb_1915fc9e');
            $table->index(['employee_id', 'status'], 'idx_41eb_de1876ba');
            $table->index(['approver_id', 'status'], 'idx_41eb_34d36ceb');
            $table->index('due_date');
        });
        }

        if (!Schema::hasTable('expense_claim_items')) {
            Schema::create('expense_claim_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('claim_id');
            $table->unsignedBigInteger('category_id')->nullable();
            $table->date('date');
            $table->string('description');
            $table->decimal('amount', 15, 4);
            $table->string('receipt_path')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('paid_by_employee')->default(true);
            $table->timestamps();

            $table->foreign('claim_id')->references('id')->on('expense_claims')->onDelete('cascade');

            $table->index(['claim_id', 'date'], 'idx_41eb_4ec2bf2d');
            $table->index(['category_id', 'date'], 'idx_41eb_28289bc1');
        });
        }

        $this->seedDefaultCategories();
    }

    public function down(): void
    {
        Schema::dropIfExists('expense_claim_items');
        Schema::dropIfExists('expense_claims');
        Schema::dropIfExists('expense_claim_categories');
    }

    protected function seedDefaultCategories(): void
    {
        $companies = DB::table('companies')->pluck('id');
        $defaults = ['Materials', 'Travel', 'Tools', 'Equipment', 'Meals', 'Misc'];

        foreach ($companies as $companyId) {
            foreach ($defaults as $name) {
                DB::table('expense_claim_categories')->insert([
                    'company_id' => $companyId,
                    'name' => $name,
                    'color' => null,
                    'enabled' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }
}
;
