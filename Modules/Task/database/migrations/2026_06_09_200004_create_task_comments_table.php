<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('task_comments', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->unsignedBigInteger('task_id');
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->text('content');
            $table->boolean('is_edited')->default(false);
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('task_id')->references('id')->on('tasks')->cascadeOnDelete();
            $table->foreign('user_id')->references('id')->on('users');
            $table->foreign('parent_id')->references('id')->on('task_comments')->nullOnDelete();

            $table->index(['task_id', 'created_at'], 'idx_comment_task');
            $table->index('parent_id', 'idx_comment_parent');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('task_comments');
    }
};
