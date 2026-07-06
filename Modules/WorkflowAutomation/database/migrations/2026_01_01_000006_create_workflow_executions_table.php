<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('workflow_executions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('workflow_id');
            $table->unsignedBigInteger('organization_id')->index();
            $table->char('run_id', 36)->unique();
            $table->string('trigger_type', 64);
            $table->string('source_module', 64)->nullable();
            $table->string('subject_type', 64)->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->unsignedBigInteger('actor_id')->nullable();
            $table->tinyInteger('status')->unsigned();
            $table->string('skip_reason', 64)->nullable();
            $table->boolean('condition_result')->nullable();
            $table->tinyInteger('steps_total')->unsigned()->default(0);
            $table->tinyInteger('steps_success')->unsigned()->default(0);
            $table->tinyInteger('steps_failed')->unsigned()->default(0);
            $table->tinyInteger('steps_scheduled')->unsigned()->default(0);
            $table->unsignedSmallInteger('duration_ms')->nullable();
            $table->dateTime('triggered_at', 3);
            $table->dateTime('executed_at', 3)->nullable();
            $table->dateTime('finished_at', 3)->nullable();
            $table->timestamp('created_at')->nullable();
            $table->index(['workflow_id', 'triggered_at']);
            $table->index(['organization_id', 'triggered_at']);
            $table->index(['status', 'triggered_at']);
            $table->index(['subject_type', 'subject_id', 'triggered_at']);
        });
    }
    public function down(): void { Schema::dropIfExists('workflow_executions'); }
};
