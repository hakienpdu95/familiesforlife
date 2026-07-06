<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('blueprint_analytics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('blueprint_version_id')->constrained('blueprint_versions')->cascadeOnDelete();
            $table->string('metric_code', 100);
            $table->string('name', 255);
            $table->text('description')->nullable();
            $table->string('metric_type', 50)->nullable(); // count|percentage|average|sum
            $table->text('formula')->nullable();
            $table->string('source_type', 50)->nullable(); // checklist|task|ai_result|file
            $table->string('status', 20)->default('active');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('blueprint_analytics');
    }
};
