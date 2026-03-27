<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('estimate_settings')) {
            Schema::create('estimate_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->unique();
            $table->string('prefix', 20)->default('EST-');
            $table->integer('next_number')->default(1);
            $table->text('default_terms')->nullable();
            $table->string('template', 50)->default('default');
            $table->boolean('approval_required')->default(true);
            $table->timestamps();

        });
        }

        if (!Schema::hasTable('estimate_histories')) {
            Schema::create('estimate_histories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('document_id');
            $table->string('status', 50);
            $table->string('notify', 1)->default('0');
            $table->text('description')->nullable();
            $table->timestamps();

            $table->index(['document_id', 'status'], 'idx_9246_7b73ea75');
        });
        }

        if (!Schema::hasTable('estimate_portal_tokens')) {
            Schema::create('estimate_portal_tokens', function (Blueprint $table) {
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
    }

    public function down(): void
    {
        Schema::dropIfExists('estimate_portal_tokens');
        Schema::dropIfExists('estimate_histories');
        Schema::dropIfExists('estimate_settings');
    }
};
