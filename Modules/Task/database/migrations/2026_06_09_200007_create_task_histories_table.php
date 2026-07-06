<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('task_histories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('task_id');
            $table->unsignedBigInteger('actor_id');
            $table->string('field_changed', 60);
            $table->string('old_value', 500)->nullable();
            $table->string('new_value', 500)->nullable();
            $table->timestamp('changed_at')->useCurrent();

            $table->foreign('task_id')->references('id')->on('tasks')->cascadeOnDelete();
            $table->foreign('actor_id')->references('id')->on('users');
            $table->index(['task_id', 'field_changed', 'changed_at'], 'idx_history_task_field');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('task_histories');
    }
};
