<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rc_interviews', function (Blueprint $table) {
            $table->id();
            $table->uuid()->nullable()->unique();
            $table->unsignedBigInteger('application_id')->index();
            $table->unsignedBigInteger('stage_id');
            $table->string('interview_type', 20)->default('video');
            $table->string('title', 200)->nullable();
            $table->timestamp('scheduled_at')->index();
            $table->smallInteger('duration_minutes')->default(60);
            $table->string('location', 300)->nullable();
            $table->text('meeting_url')->nullable();
            $table->string('meeting_id', 100)->nullable();
            $table->string('status', 20)->default('scheduled')->index();
            $table->text('interviewer_note')->nullable();
            $table->unsignedBigInteger('created_by');
            $table->timestamps();

            $table->foreign('application_id')->references('id')->on('rc_applications')->cascadeOnDelete();
            $table->foreign('stage_id')->references('id')->on('rc_pipeline_stages');
            $table->foreign('created_by')->references('id')->on('users');

            $table->index(['application_id', 'status'], 'idx_rc_interview_app');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rc_interviews');
    }
};
