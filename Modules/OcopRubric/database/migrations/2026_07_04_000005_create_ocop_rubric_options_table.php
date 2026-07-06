<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ocop_rubric_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('criterion_id')->constrained('ocop_rubric_criteria')->cascadeOnDelete();
            $table->string('label', 1000);
            $table->decimal('points', 5, 2);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['criterion_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ocop_rubric_options');
    }
};
