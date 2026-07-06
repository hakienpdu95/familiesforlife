<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rc_application_stage_logs', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->nullable()->unique();
            $table->unsignedBigInteger('application_id')->index();
            $table->unsignedBigInteger('stage_id');
            $table->string('result', 20);
            $table->text('note')->nullable();
            $table->unsignedBigInteger('actioned_by');
            $table->timestamp('actioned_at')->useCurrent()->index();

            $table->foreign('application_id')->references('id')->on('rc_applications')->cascadeOnDelete();
            $table->foreign('stage_id')->references('id')->on('rc_pipeline_stages');
            $table->foreign('actioned_by')->references('id')->on('users');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rc_application_stage_logs');
    }
};
