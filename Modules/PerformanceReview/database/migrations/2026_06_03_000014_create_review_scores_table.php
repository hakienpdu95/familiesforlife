<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('review_scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('review_id')->constrained('performance_reviews')->cascadeOnDelete();
            $table->string('criteria_key', 100);
            $table->string('criteria_name');
            $table->decimal('score', 5, 2);
            $table->decimal('weight', 5, 2);
            $table->unsignedTinyInteger('max_score');
            $table->text('comment')->nullable();
            $table->timestamps();

            $table->unique(['review_id', 'criteria_key'], 'uq_score_criteria');
        });

        DB::statement('ALTER TABLE review_scores ADD CONSTRAINT chk_rs_score CHECK (score >= 0 AND score <= max_score)');
        DB::statement('ALTER TABLE review_scores ADD CONSTRAINT chk_rs_weight CHECK (weight > 0 AND weight <= 100)');
    }

    public function down(): void
    {
        Schema::dropIfExists('review_scores');
    }
};
