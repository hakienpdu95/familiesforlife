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
        if (Schema::hasTable('sandbox_tasks')) {
            return;
        }

        Schema::create('sandbox_tasks', function (Blueprint $table) {
            $table->id();
            $table->uuid()->nullable()->unique()->comment('Public UUID — expose ra ngoài, không phải PK');
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
            $table->unsignedBigInteger('sandbox_env_id');
            $table->string('target_position_code', 50)->nullable()->comment('B1_SALES|B2_HR|... null=universal');
            $table->string('title', 255);
            $table->text('instruction')->comment('Hướng dẫn chi tiết cho người thực hành');
            $table->text('expected_output')->comment('Mô tả kết quả mong đợi');
            $table->text('scoring_rubric')->nullable()->comment('Tiêu chí chấm điểm — pipe-delimited');
            $table->unsignedSmallInteger('time_limit_minutes')->nullable();
            $table->string('ai_tools_allowed', 300)->nullable()->comment('pipe-delimited: ChatGPT|Claude|Gemini|...');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            

            // Indexes
            $table->index(['sandbox_env_id', 'target_position_code'], 'idx_sandtask_env_pos');
        });

        
    }

    public function down(): void
    {
        Schema::dropIfExists('sandbox_tasks');
    }
};