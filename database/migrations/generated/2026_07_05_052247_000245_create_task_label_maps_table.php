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
        if (Schema::hasTable('task_label_maps')) {
            return;
        }

        Schema::create('task_label_maps', function (Blueprint $table) {
            $table->id();
            $table->uuid()->nullable()->unique()->comment('Public UUID — expose ra ngoài, không phải PK');
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
            $table->foreignId('task_id')->constrained('tasks')->cascadeOnDelete();
            $table->foreignId('label_id')->constrained('task_labels')->cascadeOnDelete();
            $table->timestamp('created_at')->nullable();
            

            // Indexes
            $table->index('label_id', 'idx_task_label_maps_label');
        });

        
    }

    public function down(): void
    {
        Schema::dropIfExists('task_label_maps');
    }
};