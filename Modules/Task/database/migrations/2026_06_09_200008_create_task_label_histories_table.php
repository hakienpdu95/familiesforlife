<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('task_label_histories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('task_id');
            $table->unsignedBigInteger('label_id');
            $table->unsignedBigInteger('actor_id');
            $table->string('action', 10); // added | removed
            $table->timestamp('changed_at')->useCurrent();

            $table->foreign('task_id')->references('id')->on('tasks')->cascadeOnDelete();
            $table->foreign('label_id')->references('id')->on('task_labels');
            $table->foreign('actor_id')->references('id')->on('users');
            $table->index(['task_id', 'changed_at'], 'idx_lhist_task');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('task_label_histories');
    }
};
