<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('workflow_execution_steps', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('execution_id');
            $table->unsignedBigInteger('step_id');
            $table->tinyInteger('sort_order')->unsigned()->default(0);
            $table->string('action_type', 64);
            $table->tinyInteger('status')->unsigned();
            $table->string('error_message', 500)->nullable();
            $table->unsignedSmallInteger('duration_ms')->nullable();
            $table->tinyInteger('attempts')->unsigned()->default(1);
            $table->dateTime('executed_at', 3)->nullable();
            $table->timestamp('created_at')->nullable();
            $table->index(['execution_id', 'sort_order']);
        });
    }
    public function down(): void { Schema::dropIfExists('workflow_execution_steps'); }
};
