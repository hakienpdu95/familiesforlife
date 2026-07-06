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
        if (Schema::hasTable('sop_step_connectors')) {
            return;
        }

        Schema::create('sop_step_connectors', function (Blueprint $table) {
            $table->id();
            $table->uuid()->nullable()->unique()->comment('Public UUID — expose ra ngoài, không phải PK');
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
            $table->foreignId('sop_id')->constrained('sop_processes')->cascadeOnDelete();
            $table->foreignId('from_step_id')->constrained('sop_steps')->cascadeOnDelete();
            $table->foreignId('to_step_id')->constrained('sop_steps')->cascadeOnDelete();
            $table->enum('connector_type', ['sequence', 'yes_branch', 'no_branch', 'trigger', 'return', 'exception'])->default('sequence')->index();
            $table->string('label', 60)->nullable();
            $table->char('color_hex', 7)->nullable();
            $table->smallInteger('sort_order')->default(0);
            

            // Indexes
            $table->unique(['from_step_id', 'to_step_id', 'connector_type'], 'idx_conn_unique');
            $table->index('sop_id', 'idx_conn_sop');
            $table->index('from_step_id', 'idx_conn_from');
            $table->index('to_step_id', 'idx_conn_to');
        });

        
    }

    public function down(): void
    {
        Schema::dropIfExists('sop_step_connectors');
    }
};