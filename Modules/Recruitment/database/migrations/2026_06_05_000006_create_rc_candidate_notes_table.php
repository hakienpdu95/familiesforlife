<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rc_candidate_notes', function (Blueprint $table) {
            $table->id();
            $table->uuid()->nullable()->unique();
            $table->unsignedBigInteger('candidate_id')->index();
            $table->unsignedBigInteger('application_id')->nullable();
            $table->text('content');
            $table->string('note_type', 30)->default('general');
            $table->boolean('is_private')->default(false);
            $table->unsignedBigInteger('created_by');
            $table->timestamp('created_at')->useCurrent()->index();

            $table->foreign('candidate_id')->references('id')->on('rc_candidates')->cascadeOnDelete();
            $table->foreign('application_id')->references('id')->on('rc_applications')->nullOnDelete();
            $table->foreign('created_by')->references('id')->on('users');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rc_candidate_notes');
    }
};
