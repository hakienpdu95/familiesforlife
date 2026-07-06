<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('task_comments')) {
            return;
        }

        Schema::create('task_comments', function (Blueprint $table) {
            $table->id();
            $table->uuid()->nullable()->unique()->comment('Public UUID — expose ra ngoài, không phải PK');
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
            $table->unsignedBigInteger('task_id');
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->text('content');
            $table->boolean('is_edited')->default(false);
            $table->timestamps();
            $table->softDeletes();
            

            // Indexes
            $table->index(['task_id', 'created_at'], 'idx_comment_task');
            $table->index('parent_id', 'idx_comment_parent');
        });

        
    }

    public function down(): void
    {
        Schema::dropIfExists('task_comments');
    }
};