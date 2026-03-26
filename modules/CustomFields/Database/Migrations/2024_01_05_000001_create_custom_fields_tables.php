<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('custom_field_definitions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->string('entity_type', 50);
            $table->string('name', 255);
            $table->enum('field_type', [
                'text', 'textarea', 'number', 'date', 'datetime', 'time',
                'select', 'checkbox', 'toggle', 'url', 'email',
            ]);
            $table->boolean('required')->default(false);
            $table->text('default_value')->nullable();
            $table->json('options_json')->nullable();
            $table->integer('position')->default(0);
            $table->boolean('show_on_pdf')->default(false);
            $table->enum('width', ['full', 'half'])->default('full');
            $table->boolean('enabled')->default(true);
            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');

            $table->index('company_id');
            $table->index('entity_type');
            $table->index(['company_id', 'entity_type', 'enabled']);
        });

        Schema::create('custom_field_values', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('definition_id');
            $table->string('entity_type', 50);
            $table->unsignedBigInteger('entity_id');
            $table->text('value')->nullable();
            $table->timestamps();

            $table->foreign('definition_id')->references('id')->on('custom_field_definitions')->onDelete('cascade');

            $table->unique(['definition_id', 'entity_type', 'entity_id'], 'cfv_unique');
            $table->index(['entity_type', 'entity_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('custom_field_values');
        Schema::dropIfExists('custom_field_definitions');
    }
};
