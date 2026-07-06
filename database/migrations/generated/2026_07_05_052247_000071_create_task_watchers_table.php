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
        if (Schema::hasTable('task_watchers')) {
            return;
        }

        Schema::create('task_watchers', function (Blueprint $table) {
            $table->id();
            $table->uuid()->nullable()->unique()->comment('Public UUID — expose ra ngoài, không phải PK');
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
            $table->unsignedBigInteger('task_id');
            $table->unsignedBigInteger('user_id');
            $table->timestamp('watched_at')->useCurrent();
            

            // Indexes
            $table->index('user_id', 'idx_watcher_user');
        });

        
    }

    public function down(): void
    {
        Schema::dropIfExists('task_watchers');
    }
};