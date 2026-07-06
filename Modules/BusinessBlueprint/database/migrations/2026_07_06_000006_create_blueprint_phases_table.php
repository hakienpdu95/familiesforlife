<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('blueprint_phases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workflow_id')->constrained('blueprint_workflows')->cascadeOnDelete();
            $table->string('code', 50);
            $table->string('name', 255);
            $table->text('description')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->text('entry_condition')->nullable();
            $table->text('exit_condition')->nullable();
            $table->boolean('is_initial')->default(false);
            $table->boolean('auto_assign_data_collection')->default(false);
            $table->string('status', 20)->default('active');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('blueprint_phases');
    }
};
