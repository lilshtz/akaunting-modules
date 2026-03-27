<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('pos_orders')) {
            Schema::create('pos_orders', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('contact_id')->nullable();
            $table->string('order_number');
            $table->string('status', 30)->default('completed');
            $table->decimal('subtotal', 15, 4)->default(0);
            $table->decimal('tax', 15, 4)->default(0);
            $table->decimal('discount', 15, 4)->default(0);
            $table->decimal('total', 15, 4)->default(0);
            $table->string('payment_method', 50)->default('cash');
            $table->decimal('paid_amount', 15, 4)->default(0);
            $table->decimal('change_amount', 15, 4)->default(0);
            $table->string('tab_name')->nullable();
            $table->timestamps();

            $table->unique(['company_id', 'order_number']);
            $table->index(['company_id', 'status', 'created_at'], 'idx_3225_605dc3a5');
        });
        }

        if (!Schema::hasTable('pos_order_items')) {
            Schema::create('pos_order_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_id');
            $table->unsignedBigInteger('item_id')->nullable();
            $table->string('name');
            $table->string('sku')->nullable();
            $table->decimal('quantity', 15, 4)->default(0);
            $table->decimal('price', 15, 4)->default(0);
            $table->decimal('discount', 15, 4)->default(0);
            $table->decimal('tax', 15, 4)->default(0);
            $table->decimal('total', 15, 4)->default(0);
            $table->timestamps();

            $table->foreign('order_id')->references('id')->on('pos_orders')->onDelete('cascade');
            $table->index(['order_id', 'item_id'], 'idx_3225_34bc780d');
        });
        }

        if (!Schema::hasTable('pos_settings')) {
            Schema::create('pos_settings', function (Blueprint $table) {
            $table->unsignedBigInteger('company_id')->primary();
            $table->unsignedInteger('receipt_width')->default(80);
            $table->string('default_payment_method', 50)->default('cash');
            $table->boolean('auto_create_invoice')->default(false);
            $table->unsignedBigInteger('next_order_number')->default(1);

        });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('pos_settings');
        Schema::dropIfExists('pos_order_items');
        Schema::dropIfExists('pos_orders');
    }
};
