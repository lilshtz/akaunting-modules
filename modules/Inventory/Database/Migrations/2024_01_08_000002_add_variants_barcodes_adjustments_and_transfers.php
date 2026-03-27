<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('inventory_variants')) {
            Schema::create('inventory_variants', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('item_id');
            $table->string('name');
            $table->string('sku')->unique();
            $table->json('attributes_json')->nullable();
            $table->decimal('cost_price', 15, 4)->nullable();
            $table->decimal('sale_price', 15, 4)->nullable();
            $table->timestamps();

            $table->index(['item_id', 'name'], 'idx_183d_e37eb0ca');
        });
        }

        Schema::table('inventory_stock', function (Blueprint $table) {
            $table->unsignedBigInteger('variant_id')->nullable()->after('item_id');
            $table->foreign('variant_id')->references('id')->on('inventory_variants')->nullOnDelete();
            $table->index(['item_id', 'variant_id', 'warehouse_id'], 'idx_183d_8104efd5');
        });

        Schema::table('inventory_history', function (Blueprint $table) {
            $table->unsignedBigInteger('variant_id')->nullable()->after('item_id');
            $table->foreign('variant_id')->references('id')->on('inventory_variants')->nullOnDelete();
            $table->index(['company_id', 'variant_id'], 'idx_183d_606f7e4a');
        });

        if (!Schema::hasTable('inventory_adjustments')) {
            Schema::create('inventory_adjustments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('warehouse_id');
            $table->unsignedBigInteger('item_id');
            $table->unsignedBigInteger('variant_id')->nullable();
            $table->decimal('quantity', 15, 4);
            $table->string('reason', 30);
            $table->text('description')->nullable();
            $table->dateTime('date');
            $table->unsignedBigInteger('user_id');
            $table->timestamps();

            $table->foreign('warehouse_id')->references('id')->on('inventory_warehouses')->onDelete('cascade');
            $table->foreign('variant_id')->references('id')->on('inventory_variants')->nullOnDelete();
            $table->index(['company_id', 'warehouse_id', 'date'], 'idx_183d_11477874');
            $table->index(['item_id', 'variant_id'], 'idx_183d_6ae9628d');
        });
        }

        if (!Schema::hasTable('inventory_transfer_orders')) {
            Schema::create('inventory_transfer_orders', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('from_warehouse_id');
            $table->unsignedBigInteger('to_warehouse_id');
            $table->string('status', 20)->default('draft');
            $table->dateTime('date');
            $table->text('description')->nullable();
            $table->timestamps();

            $table->foreign('from_warehouse_id')->references('id')->on('inventory_warehouses')->onDelete('cascade');
            $table->foreign('to_warehouse_id')->references('id')->on('inventory_warehouses')->onDelete('cascade');
            $table->index(['company_id', 'status', 'date'], 'idx_183d_9bedbef9');
        });
        }

        if (!Schema::hasTable('inventory_transfer_items')) {
            Schema::create('inventory_transfer_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('transfer_order_id');
            $table->unsignedBigInteger('item_id');
            $table->unsignedBigInteger('variant_id')->nullable();
            $table->decimal('quantity', 15, 4);
            $table->timestamps();

            $table->foreign('transfer_order_id')->references('id')->on('inventory_transfer_orders')->onDelete('cascade');
            $table->foreign('variant_id')->references('id')->on('inventory_variants')->nullOnDelete();
            $table->index(['transfer_order_id', 'item_id'], 'idx_183d_8265b40a');
        });
        }

        if (!Schema::hasTable('inventory_item_groups')) {
            Schema::create('inventory_item_groups', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->string('name');
            $table->text('description')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'name'], 'idx_183d_516a0742');
        });
        }

        if (!Schema::hasTable('inventory_item_group_items')) {
            Schema::create('inventory_item_group_items', function (Blueprint $table) {
            $table->unsignedBigInteger('item_group_id');
            $table->unsignedBigInteger('item_id');

            $table->primary(['item_group_id', 'item_id']);
            $table->foreign('item_group_id')->references('id')->on('inventory_item_groups')->onDelete('cascade');
        });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_item_group_items');
        Schema::dropIfExists('inventory_item_groups');
        Schema::dropIfExists('inventory_transfer_items');
        Schema::dropIfExists('inventory_transfer_orders');
        Schema::dropIfExists('inventory_adjustments');

        Schema::table('inventory_history', function (Blueprint $table) {
            $table->dropForeign(['variant_id']);
            $table->dropColumn('variant_id');
        });

        Schema::table('inventory_stock', function (Blueprint $table) {
            $table->dropForeign(['variant_id']);
            $table->dropColumn('variant_id');
            $table->unique(['item_id', 'warehouse_id']);
        });

        Schema::dropIfExists('inventory_variants');
    }
};
