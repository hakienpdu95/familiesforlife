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
        if (Schema::hasTable('task_histories')) {
            return;
        }

        Schema::create('task_histories', function (Blueprint $table) {
            $table->id();
            $table->uuid()->nullable()->unique()->comment('Public UUID — expose ra ngoài, không phải PK');
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
            $table->unsignedBigInteger('task_id');
            $table->unsignedBigInteger('actor_id');
            $table->string('field_changed', 60);
            $table->string('old_value', 500)->nullable();
            $table->string('new_value', 500)->nullable();
            $table->timestamp('changed_at')->useCurrent();
            

            // Indexes
            $table->index(['task_id', 'field_changed', 'changed_at'], 'idx_history_task_field');
        });

        
    }

    public function down(): void
    {
        Schema::dropIfExists('task_histories');
    }
};