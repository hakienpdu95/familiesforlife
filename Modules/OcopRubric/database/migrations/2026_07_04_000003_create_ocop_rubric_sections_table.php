<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ocop_rubric_sections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rubric_version_id')->constrained('ocop_rubric_versions')->cascadeOnDelete();
            $table->string('code', 5);                                // 'A' | 'B' | 'C'
            $table->string('label', 255);
            $table->decimal('max_score', 5, 2);                       // 40.00 / 25.00 / 35.00
            $table->unsignedTinyInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['rubric_version_id', 'code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ocop_rubric_sections');
    }
};
