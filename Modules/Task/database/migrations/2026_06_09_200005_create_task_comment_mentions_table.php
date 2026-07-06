<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('task_comment_mentions', function (Blueprint $table) {
            $table->unsignedBigInteger('comment_id');
            $table->unsignedBigInteger('user_id');
            $table->timestamp('created_at')->useCurrent();

            $table->primary(['comment_id', 'user_id']);
            $table->foreign('comment_id')->references('id')->on('task_comments')->cascadeOnDelete();
            $table->foreign('user_id')->references('id')->on('users');
            $table->index('user_id', 'idx_mention_user');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('task_comment_mentions');
    }
};
