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
        if (Schema::hasTable('task_comment_mentions')) {
            return;
        }

        Schema::create('task_comment_mentions', function (Blueprint $table) {
            $table->id();
            $table->uuid()->nullable()->unique()->comment('Public UUID — expose ra ngoài, không phải PK');
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
            $table->unsignedBigInteger('comment_id');
            $table->unsignedBigInteger('user_id');
            $table->timestamp('created_at')->nullable();
            

            // Indexes
            $table->index('user_id', 'idx_mention_user');
        });

        
    }

    public function down(): void
    {
        Schema::dropIfExists('task_comment_mentions');
    }
};