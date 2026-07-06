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
        if (Schema::hasTable('sop_steps')) {
            return;
        }

        Schema::create('sop_steps', function (Blueprint $table) {
            $table->id();
            $table->uuid()->nullable()->unique()->comment('Public UUID — expose ra ngoài, không phải PK');
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
            $table->foreignId('sop_id')->constrained('sop_processes')->cascadeOnDelete();
            $table->unsignedSmallInteger('position');
            $table->string('title', 200);
            $table->text('description')->nullable();
            $table->text('expected_output')->nullable();
            $table->text('warning_note')->nullable();
            $table->enum('step_type', ['start', 'end', 'action', 'decision', 'sub_sop', 'notification', 'wait'])->default('action')->index();
            $table->foreignId('ref_sop_id')->nullable()->constrained('sop_processes')->nullOnDelete();
            $table->unsignedSmallInteger('branch_yes_position')->nullable();
            $table->unsignedSmallInteger('branch_no_position')->nullable();
            $table->unsignedSmallInteger('duration_minutes')->nullable();
            $table->boolean('is_mandatory')->default(true);
            $table->boolean('is_active')->default(true)->index();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            

            // Indexes
            $table->unique(['sop_id', 'position'], 'idx_sop_step_pos_unique');
            $table->index(['sop_id', 'is_active', 'position'], 'idx_step_render');
            $table->index(['sop_id', 'step_type', 'is_active'], 'idx_step_type_filter');
            $table->index('ref_sop_id', 'idx_step_ref_sop');
        });

        
    }

    public function down(): void
    {
        Schema::dropIfExists('sop_steps');
    }
};