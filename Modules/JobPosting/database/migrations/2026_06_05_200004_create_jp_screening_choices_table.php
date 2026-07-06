<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('jp_screening_choices', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->nullable()->unique();
            $table->foreignId('question_id')->constrained('jp_screening_questions')->cascadeOnDelete();
            $table->string('choice_text', 200);
            $table->boolean('is_disqualifying')->default(false);
            $table->smallInteger('sort_order')->default(0);

            $table->index(['question_id', 'sort_order'], 'idx_jp_choice_question');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jp_screening_choices');
    }
};
