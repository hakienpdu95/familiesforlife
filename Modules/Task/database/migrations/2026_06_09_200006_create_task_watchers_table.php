<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('task_watchers', function (Blueprint $table) {
            $table->unsignedBigInteger('task_id');
            $table->unsignedBigInteger('user_id');
            $table->timestamp('watched_at')->useCurrent();

            $table->primary(['task_id', 'user_id']);
            $table->foreign('task_id')->references('id')->on('tasks')->cascadeOnDelete();
            $table->foreign('user_id')->references('id')->on('users');
            $table->index('user_id', 'idx_watcher_user');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('task_watchers');
    }
};
