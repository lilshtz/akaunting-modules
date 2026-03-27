<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('inventory_warehouses')) {
            Schema::create('inventory_warehouses', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->string('name');
            $table->text('address')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->boolean('enabled')->default(true);
            $table->timestamps();

            $table->index(['company_id', 'enabled'], 'idx_dfd9_8272d36f');
        });
        }

        if (!Schema::hasTable('inventory_stock')) {
            Schema::create('inventory_stock', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('item_id');
            $table->unsignedBigInteger('warehouse_id');
            $table->decimal('quantity', 15, 4)->default(0);
            $table->decimal('reorder_level', 15, 4)->nullable();
            $table->timestamps();

            $table->foreign('warehouse_id')->references('id')->on('inventory_warehouses')->onDelete('cascade');
            $table->unique(['item_id', 'warehouse_id']);
            $table->index('warehouse_id');
        });
        }

        if (!Schema::hasTable('inventory_history')) {
            Schema::create('inventory_history', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('item_id');
            $table->unsignedBigInteger('warehouse_id');
            $table->decimal('quantity_change', 15, 4);
            $table->string('type', 30);
            $table->string('reference_type')->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->text('description')->nullable();
            $table->dateTime('date');
            $table->timestamps();

            $table->foreign('warehouse_id')->references('id')->on('inventory_warehouses')->onDelete('cascade');
            $table->index(['company_id', 'type'], 'idx_dfd9_ca627f5b');
            $table->index(['reference_type', 'reference_id'], 'idx_dfd9_9991dc83');
            $table->index('date');
        });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_history');
        Schema::dropIfExists('inventory_stock');
        Schema::dropIfExists('inventory_warehouses');
    }
};
