<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('business_solution_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_solution_id')->constrained('business_solutions')->cascadeOnDelete();
            $table->string('version', 30);              // "1.0.0"
            $table->string('status', 20)->default('draft'); // draft|published|deprecated
            $table->text('release_note')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->unsignedBigInteger('published_by')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['business_solution_id', 'version']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('business_solution_versions');
    }
};
