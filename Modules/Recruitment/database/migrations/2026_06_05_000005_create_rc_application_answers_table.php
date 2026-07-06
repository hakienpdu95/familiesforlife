<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rc_application_answers', function (Blueprint $table) {
            $table->id();
            $table->uuid()->nullable()->unique();
            $table->unsignedBigInteger('application_id')->index();
            $table->char('jp_question_id', 36)->index();
            $table->string('question_text', 500);
            $table->string('question_type', 30);
            $table->text('answer_text')->nullable();
            $table->boolean('answer_bool')->nullable();
            $table->string('answer_choices', 500)->nullable();
            $table->boolean('is_disqualifying')->default(false);

            $table->foreign('application_id')->references('id')->on('rc_applications')->cascadeOnDelete();

            $table->index(['application_id', 'is_disqualifying'], 'idx_rc_answer_disq');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rc_application_answers');
    }
};
