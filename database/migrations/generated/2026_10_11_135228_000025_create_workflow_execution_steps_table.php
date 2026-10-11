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
        if (Schema::hasTable('workflow_execution_steps')) {
            return;
        }

        Schema::create('workflow_execution_steps', function (Blueprint $table) {
            $table->id();
            $table->uuid()->nullable()->unique()->comment('Public UUID — expose ra ngoài, không phải PK');
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
            $table->unsignedBigInteger('execution_id');
            $table->unsignedBigInteger('step_id');
            $table->unsignedTinyInteger('sort_order')->default(0);
            $table->string('action_type', 64);
            $table->unsignedTinyInteger('status');
            $table->string('error_message', 500)->nullable();
            $table->unsignedSmallInteger('duration_ms')->nullable();
            $table->unsignedTinyInteger('attempts')->default(1);
            $table->dateTime('executed_at')->nullable();
            $table->timestamp('created_at')->nullable();
            

            // Indexes
            $table->index(['execution_id', 'sort_order']);
        });

        
    }

    public function down(): void
    {
        Schema::dropIfExists('workflow_execution_steps');
    }
};