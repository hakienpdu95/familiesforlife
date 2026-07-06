<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sop_step_versions', function (Blueprint $table) {
            $table->id();
            $table->uuid()->nullable()->unique()->comment('Public UUID — expose ra ngoài, không phải PK');
            $table->foreignId('sop_version_id')->constrained('sop_versions')->cascadeOnDelete();
            $table->foreignId('original_step_id')
                  ->nullable()
                  ->constrained('sop_steps')
                  ->nullOnDelete(); // SET NULL — giữ history kể cả khi bước gốc bị xóa
            $table->smallInteger('position')->unsigned();
            $table->string('title', 200);
            $table->text('description')->nullable();
            $table->text('expected_output')->nullable();
            $table->text('warning_note')->nullable();
            $table->enum('step_type', [
                'start', 'end', 'action', 'decision',
                'sub_sop', 'notification', 'wait',
            ]);
            // Snapshot — không FK, SOP đích có thể bị xóa sau khi snapshot
            $table->unsignedBigInteger('ref_sop_id')->nullable();
            $table->string('ref_sop_code', 50)->nullable();
            $table->smallInteger('branch_yes_position')->unsigned()->nullable();
            $table->smallInteger('branch_no_position')->unsigned()->nullable();
            $table->smallInteger('duration_minutes')->unsigned()->nullable();
            $table->boolean('is_mandatory')->default(true);
            $table->enum('change_type', ['added', 'modified', 'deleted', 'unchanged'])->default('unchanged');

            $table->index(['sop_version_id', 'position'], 'idx_step_ver_pos');
            $table->index('original_step_id', 'idx_step_ver_orig');
            $table->index(['sop_version_id', 'change_type'], 'idx_step_ver_diff');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sop_step_versions');
    }
};
