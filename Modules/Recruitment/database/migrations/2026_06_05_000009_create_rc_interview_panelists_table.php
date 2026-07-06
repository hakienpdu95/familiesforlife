<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rc_interview_panelists', function (Blueprint $table) {
            $table->id();
            $table->uuid()->nullable()->unique();
            $table->unsignedBigInteger('interview_id')->index();
            $table->unsignedBigInteger('user_id');
            $table->string('role', 20)->default('interviewer');
            $table->string('response_status', 20)->default('pending');
            $table->timestamp('responded_at')->nullable();

            $table->foreign('interview_id')->references('id')->on('rc_interviews')->cascadeOnDelete();
            $table->foreign('user_id')->references('id')->on('users');

            $table->unique(['interview_id', 'user_id'], 'idx_rc_panelist_unique');
            $table->index(['user_id', 'response_status'], 'idx_rc_panelist_user');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rc_interview_panelists');
    }
};
