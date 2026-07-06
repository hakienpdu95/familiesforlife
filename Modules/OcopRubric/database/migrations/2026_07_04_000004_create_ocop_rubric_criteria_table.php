<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ocop_rubric_criteria', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rubric_section_id')->constrained('ocop_rubric_sections')->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('ocop_rubric_criteria')->cascadeOnDelete();
            $table->string('path', 255)->default('/');                // materialized path — cùng pattern production_areas
            $table->unsignedTinyInteger('depth')->default(0);
            $table->string('code', 20);                               // '1' | '1.1' | '1.2.3'
            $table->string('label', 500);
            $table->decimal('max_score', 5, 2);
            $table->text('requirement_note')->nullable();              // "Yêu cầu: 100% sản phẩm được trồng..."
            $table->boolean('is_scorable')->default(false);            // true=lá có option, false=container cộng dồn
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['rubric_section_id', 'code']);
            $table->index(['rubric_section_id', 'path']);
            $table->index(['parent_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ocop_rubric_criteria');
    }
};
