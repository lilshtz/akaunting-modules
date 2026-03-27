<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('projects')) {
            Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('contact_id')->nullable();
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('status', ['active', 'completed', 'on_hold', 'cancelled'])->default('active');
            $table->enum('billing_type', ['project_hours', 'task_hours', 'fixed_rate'])->default('fixed_rate');
            $table->decimal('billing_rate', 15, 4)->nullable();
            $table->decimal('budget', 15, 4)->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['company_id', 'status'], 'idx_065f_1915fc9e');
            $table->index('contact_id');
        });
        }

        if (!Schema::hasTable('project_milestones')) {
            Schema::create('project_milestones', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('project_id');
            $table->string('name');
            $table->text('description')->nullable();
            $table->date('target_date')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->integer('position')->default(0);
            $table->timestamps();

            $table->foreign('project_id')->references('id')->on('projects')->onDelete('cascade');
            $table->index(['project_id', 'position'], 'idx_065f_8e268eaf');
        });
        }

        if (!Schema::hasTable('project_tasks')) {
            Schema::create('project_tasks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('milestone_id')->nullable();
            $table->unsignedBigInteger('project_id');
            $table->string('name');
            $table->text('description')->nullable();
            $table->unsignedBigInteger('assignee_id')->nullable();
            $table->enum('priority', ['low', 'medium', 'high', 'critical'])->default('medium');
            $table->enum('status', ['todo', 'in_progress', 'review', 'done'])->default('todo');
            $table->decimal('estimated_hours', 15, 4)->nullable();
            $table->integer('position')->default(0);
            $table->timestamps();

            $table->foreign('milestone_id')->references('id')->on('project_milestones')->onDelete('set null');
            $table->foreign('project_id')->references('id')->on('projects')->onDelete('cascade');
            $table->foreign('assignee_id')->references('id')->on('employees')->onDelete('set null');
            $table->index(['project_id', 'status'], 'idx_065f_1ba44618');
            $table->index(['milestone_id', 'position'], 'idx_065f_41e73b79');
        });
        }

        if (!Schema::hasTable('project_members')) {
            Schema::create('project_members', function (Blueprint $table) {
            $table->unsignedBigInteger('project_id');
            $table->unsignedBigInteger('user_id');
            $table->enum('role', ['manager', 'member'])->default('member');
            $table->timestamps();

            $table->primary(['project_id', 'user_id']);
            $table->foreign('project_id')->references('id')->on('projects')->onDelete('cascade');
        });
        }

        if (!Schema::hasTable('project_discussions')) {
            Schema::create('project_discussions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('project_id');
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->text('body');
            $table->timestamps();

            $table->foreign('project_id')->references('id')->on('projects')->onDelete('cascade');
            $table->foreign('parent_id')->references('id')->on('project_discussions')->onDelete('cascade');
            $table->index(['project_id', 'created_at'], 'idx_065f_e623414a');
        });
        }

        if (!Schema::hasTable('project_transactions')) {
            Schema::create('project_transactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('project_id');
            $table->enum('document_type', ['invoice', 'bill']);
            $table->unsignedBigInteger('document_id');
            $table->timestamps();

            $table->foreign('project_id')->references('id')->on('projects')->onDelete('cascade');
            $table->unique(['project_id', 'document_type', 'document_id'], 'project_documents_unique');
            $table->index(['document_type', 'document_id'], 'idx_065f_357d01e8');
        });
        }

        if (!Schema::hasTable('project_activities')) {
            Schema::create('project_activities', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('project_id');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('action', 100);
            $table->string('entity_type', 100)->nullable();
            $table->unsignedBigInteger('entity_id')->nullable();
            $table->text('description')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('project_id')->references('id')->on('projects')->onDelete('cascade');
            $table->index(['project_id', 'created_at'], 'idx_065f_e623414a');
        });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('project_activities');
        Schema::dropIfExists('project_transactions');
        Schema::dropIfExists('project_discussions');
        Schema::dropIfExists('project_members');
        Schema::dropIfExists('project_tasks');
        Schema::dropIfExists('project_milestones');
        Schema::dropIfExists('projects');
    }
};
