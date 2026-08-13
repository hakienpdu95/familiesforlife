<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** spec/Entity_Comparison_Module_Technical_Spec.md §3.6 — pivot gán tiêu chí vào loại đối tượng. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('entity_type_criterion', function (Blueprint $table) {
            $table->id();
            $table->foreignId('entity_type_id')->constrained('entity_types')->cascadeOnDelete();
            $table->foreignId('criterion_id')->constrained('criteria')->cascadeOnDelete();
            $table->boolean('is_required')->default(false);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['entity_type_id', 'criterion_id'], 'uq_entity_type_criterion');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('entity_type_criterion');
    }
};
