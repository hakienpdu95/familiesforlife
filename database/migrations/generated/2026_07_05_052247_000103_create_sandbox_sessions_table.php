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
        if (Schema::hasTable('sandbox_sessions')) {
            return;
        }

        Schema::create('sandbox_sessions', function (Blueprint $table) {
            $table->id();
            $table->uuid()->nullable()->unique()->comment('Public UUID — expose ra ngoài, không phải PK');
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
            $table->unsignedBigInteger('organization_id');
            $table->unsignedBigInteger('sandbox_task_id');
            $table->unsignedBigInteger('workforce_profile_id')->nullable();
            $table->unsignedBigInteger('user_id');
            $table->string('status', 20)->default('in_progress')->comment('in_progress|submitted|evaluating|completed|abandoned');
            $table->timestamp('started_at')->useCurrent();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->unsignedSmallInteger('duration_minutes')->nullable();
            $table->decimal('quality_score', 5, 2)->nullable()->comment('40% — chấm theo scoring_rubric');
            $table->decimal('productivity_score', 5, 2)->nullable()->comment('35% — duration vs time_limit');
            $table->decimal('ai_adoption_score', 5, 2)->nullable()->comment('25% — tỷ lệ dùng AI');
            $table->decimal('final_score', 5, 2)->nullable();
            $table->boolean('passed')->nullable();
            $table->unsignedBigInteger('evaluator_user_id')->nullable();
            $table->timestamp('evaluated_at')->nullable();
            $table->text('feedback')->nullable();
            $table->timestamps();
            

            // Indexes
            $table->index(['workforce_profile_id', 'status'], 'idx_sandsess_profile_status');
            $table->index(['sandbox_task_id', 'status'], 'idx_sandsess_task_status');
        });

        
    }

    public function down(): void
    {
        Schema::dropIfExists('sandbox_sessions');
    }
};