<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('workflow_user_tasks', function (Blueprint $table) {
            $table->id();
            $table->char('task_token', 36)->unique();
            $table->unsignedBigInteger('execution_id');
            $table->unsignedBigInteger('step_id');
            $table->unsignedBigInteger('workflow_id');
            $table->unsignedBigInteger('organization_id')->index();
            $table->unsignedBigInteger('assignee_id')->nullable();
            $table->string('assignee_role', 64)->nullable();
            $table->string('title', 191);
            $table->text('description')->nullable();
            $table->text('context_snapshot')->nullable();
            $table->text('form_config')->nullable();
            $table->text('allowed_decisions')->nullable();
            $table->dateTime('due_at')->nullable();
            $table->string('on_timeout', 32)->default('fail');
            $table->tinyInteger('status')->unsigned()->default(1);
            // 1=pending 2=completed 3=rejected 4=expired 5=cancelled
            $table->string('decision', 64)->nullable();
            $table->text('form_response')->nullable();
            $table->string('comment', 500)->nullable();
            $table->unsignedBigInteger('completed_by')->nullable();
            $table->dateTime('completed_at')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->index(['execution_id', 'step_id']);
            $table->index(['assignee_id', 'status']);
            $table->index(['assignee_role', 'status', 'organization_id']);
            $table->index(['status', 'due_at']);
        });
    }
    public function down(): void { Schema::dropIfExists('workflow_user_tasks'); }
};
