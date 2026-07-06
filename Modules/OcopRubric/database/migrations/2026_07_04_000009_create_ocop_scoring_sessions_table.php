<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ocop_scoring_sessions', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('organization_id')->constrained()->restrictOnDelete();
            $table->foreignId('ocop_product_id')->nullable()->constrained('ocop_products')->nullOnDelete();
            $table->foreignId('rubric_version_id')->constrained('ocop_rubric_versions')->restrictOnDelete();
            $table->foreignId('duplicated_from_session_id')->nullable()
                ->constrained('ocop_scoring_sessions')->nullOnDelete();  // lineage — xem §8.4
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('employee_id')->nullable();     // liên kết KpiGoal/PerformanceReview tương lai
            $table->string('mode', 20);                                 // ScoringSessionMode
            $table->string('status', 20)->default('in_progress');       // ScoringSessionStatus
            $table->boolean('is_locked')->default(false);               // true ngay khi completed — chặn sửa vĩnh viễn
            $table->decimal('score_section_a', 5, 2)->default(0);
            $table->decimal('score_section_b', 5, 2)->default(0);
            $table->decimal('score_section_c', 5, 2)->default(0);
            $table->decimal('total_score', 5, 2)->default(0);
            $table->unsignedTinyInteger('star_rank')->nullable();
            $table->unsignedSmallInteger('criteria_total')->default(0);
            $table->unsignedSmallInteger('criteria_answered')->default(0);
            $table->unsignedInteger('duration_seconds')->nullable();
            $table->timestamp('started_at')->useCurrent();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['organization_id', 'mode', 'status']);
            $table->index(['ocop_product_id', 'mode']);
            $table->index(['user_id', 'mode']);
            $table->index('duplicated_from_session_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ocop_scoring_sessions');
    }
};
