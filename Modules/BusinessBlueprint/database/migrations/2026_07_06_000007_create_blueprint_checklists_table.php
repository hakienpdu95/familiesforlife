<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('blueprint_checklists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('phase_id')->constrained('blueprint_phases')->cascadeOnDelete();
            $table->string('code', 50);
            $table->string('name', 255);
            $table->text('description')->nullable();
            $table->text('input_description')->nullable();
            $table->text('action_description')->nullable();
            $table->text('output_description')->nullable();
            $table->boolean('required')->default(true);
            $table->string('default_priority', 20)->default('normal'); // low|normal|high
            $table->decimal('estimated_hours', 6, 2)->nullable();
            $table->boolean('need_approval')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->string('status', 20)->default('active');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('blueprint_checklists');
    }
};
