<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rc_candidate_attachments', function (Blueprint $table) {
            $table->id();
            $table->uuid()->nullable()->unique();
            $table->unsignedBigInteger('candidate_id')->index();
            $table->unsignedBigInteger('application_id')->nullable();
            $table->string('file_type', 20)->default('cv');
            $table->string('file_name', 255);
            $table->text('file_url');
            $table->integer('file_size_kb');
            $table->string('storage_provider', 20)->default('local');
            $table->string('storage_key', 500);
            $table->unsignedBigInteger('uploaded_by');
            $table->timestamp('uploaded_at')->useCurrent();

            $table->foreign('candidate_id')->references('id')->on('rc_candidates')->cascadeOnDelete();
            $table->foreign('application_id')->references('id')->on('rc_applications')->nullOnDelete();
            $table->foreign('uploaded_by')->references('id')->on('users');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rc_candidate_attachments');
    }
};
