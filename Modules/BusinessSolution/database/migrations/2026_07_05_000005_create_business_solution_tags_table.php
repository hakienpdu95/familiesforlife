<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('business_solution_tags', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_solution_id')->constrained('business_solutions')->cascadeOnDelete();
            $table->string('tag', 100);
            $table->timestamps();

            $table->index(['business_solution_id', 'tag']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('business_solution_tags');
    }
};
