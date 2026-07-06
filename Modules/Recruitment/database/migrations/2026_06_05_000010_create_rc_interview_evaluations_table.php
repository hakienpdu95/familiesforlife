<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rc_interview_evaluations', function (Blueprint $table) {
            $table->id();
            $table->uuid()->nullable()->unique();
            $table->unsignedBigInteger('interview_id')->index();
            $table->unsignedBigInteger('evaluator_id');
            $table->smallInteger('overall_score');
            $table->text('strengths')->nullable();
            $table->text('weaknesses')->nullable();
            $table->text('recommendation')->nullable();
            $table->string('verdict', 20);
            $table->boolean('is_submitted')->default(false);
            $table->timestamp('submitted_at')->nullable();

            $table->foreign('interview_id')->references('id')->on('rc_interviews')->cascadeOnDelete();
            $table->foreign('evaluator_id')->references('id')->on('users');

            $table->unique(['interview_id', 'evaluator_id'], 'idx_rc_eval_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rc_interview_evaluations');
    }
};
