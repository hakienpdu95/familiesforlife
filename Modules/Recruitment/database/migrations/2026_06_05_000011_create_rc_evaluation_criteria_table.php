<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rc_evaluation_criteria', function (Blueprint $table) {
            $table->id();
            $table->uuid()->nullable()->unique();
            $table->unsignedBigInteger('evaluation_id')->index();
            $table->string('criterion_name', 100);
            $table->smallInteger('score');
            $table->text('comment')->nullable();

            $table->foreign('evaluation_id')->references('id')->on('rc_interview_evaluations')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rc_evaluation_criteria');
    }
};
