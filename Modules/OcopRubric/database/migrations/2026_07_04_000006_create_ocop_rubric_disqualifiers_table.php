<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ocop_rubric_disqualifiers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rubric_version_id')->constrained('ocop_rubric_versions')->cascadeOnDelete();
            $table->text('label');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ocop_rubric_disqualifiers');
    }
};
