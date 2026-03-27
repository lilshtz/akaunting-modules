<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('order_settings')) {
            Schema::create('order_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->unique();
            $table->string('so_prefix', 20)->default('SO-');
            $table->integer('so_next_number')->default(1);
            $table->string('po_prefix', 20)->default('PO-');
            $table->integer('po_next_number')->default(1);
            $table->text('default_terms')->nullable();
            $table->string('template', 50)->default('default');
            $table->timestamps();

        });
        }

        if (!Schema::hasTable('order_histories')) {
            Schema::create('order_histories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('document_id');
            $table->string('status', 50);
            $table->string('notify', 1)->default('0');
            $table->text('description')->nullable();
            $table->timestamps();

            $table->index(['document_id', 'status'], 'idx_d117_7b73ea75');
        });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('order_histories');
        Schema::dropIfExists('order_settings');
    }
};
