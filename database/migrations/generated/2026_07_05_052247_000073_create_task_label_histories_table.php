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
        if (Schema::hasTable('task_label_histories')) {
            return;
        }

        Schema::create('task_label_histories', function (Blueprint $table) {
            $table->id();
            $table->uuid()->nullable()->unique()->comment('Public UUID — expose ra ngoài, không phải PK');
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
            $table->unsignedBigInteger('task_id');
            $table->unsignedBigInteger('label_id');
            $table->unsignedBigInteger('actor_id');
            $table->string('action', 10);
            $table->timestamp('changed_at')->useCurrent();
            

            // Indexes
            $table->index(['task_id', 'changed_at'], 'idx_lhist_task');
        });

        
    }

    public function down(): void
    {
        Schema::dropIfExists('task_label_histories');
    }
};