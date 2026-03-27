<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('role_module_permissions')) {
            Schema::create('role_module_permissions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('role_id');
            $table->string('module_alias');
            $table->boolean('can_view')->default(false);
            $table->boolean('can_create')->default(false);
            $table->boolean('can_edit')->default(false);
            $table->boolean('can_delete')->default(false);
            $table->timestamps();

            $table->unique(['company_id', 'role_id', 'module_alias'], 'role_module_permissions_unique');
            $table->index(['company_id', 'module_alias'], 'idx_448d_9caaa96c');
        });
        }

        if (!Schema::hasTable('user_company_roles')) {
            Schema::create('user_company_roles', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('role_id');
            $table->timestamps();

            $table->unique(['company_id', 'user_id'], 'user_company_roles_unique');
            $table->index(['company_id', 'role_id'], 'idx_448d_05f048e2');
        });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('user_company_roles');
        Schema::dropIfExists('role_module_permissions');
    }
};
