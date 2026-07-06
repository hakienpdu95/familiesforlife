<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('task_label_maps', function (Blueprint $table) {
            $table->foreignId('task_id')->constrained('tasks')->cascadeOnDelete();
            $table->foreignId('label_id')->constrained('task_labels')->cascadeOnDelete();
            $table->timestamp('created_at')->useCurrent();

            $table->primary(['task_id', 'label_id']);
            $table->index('label_id', 'idx_task_label_maps_label');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('task_label_maps');
    }
};
