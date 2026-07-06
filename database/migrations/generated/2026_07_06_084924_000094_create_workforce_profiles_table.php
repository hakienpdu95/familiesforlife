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
        if (Schema::hasTable('workforce_profiles')) {
            return;
        }

        Schema::create('workforce_profiles', function (Blueprint $table) {
            $table->id();
            $table->uuid()->nullable()->unique()->comment('Public UUID — expose ra ngoài, không phải PK');
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
            $table->unsignedBigInteger('organization_id');
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('employee_id')->nullable();
            $table->decimal('tdwcf_score', 5, 2)->nullable();
            $table->string('tdwcf_maturity_level', 64)->nullable();
            $table->timestamp('tdwcf_assessed_at')->nullable();
            $table->decimal('score_d1_digital_literacy', 5, 2)->nullable();
            $table->decimal('score_d2_data_literacy', 5, 2)->nullable();
            $table->decimal('score_d3_ai_literacy', 5, 2)->nullable();
            $table->decimal('score_d4_workflow', 5, 2)->nullable();
            $table->decimal('score_d5_innovation', 5, 2)->nullable();
            $table->decimal('score_d6_performance', 5, 2)->nullable();
            $table->decimal('digital_score', 5, 2)->nullable()->comment('D1+D2+D3 tổng hợp');
            $table->decimal('ai_score', 5, 2)->nullable()->comment('= score_d3_ai_literacy');
            $table->decimal('productivity_score', 5, 2)->nullable()->comment('D4+D6 tổng hợp');
            $table->decimal('innovation_score', 5, 2)->nullable()->comment('= score_d5_innovation');
            $table->decimal('growth_score', 5, 2)->nullable()->comment('Tăng trưởng so với lần đánh giá trước');
            $table->decimal('ai_readiness_score', 5, 2)->nullable()->comment('(D3+D4)/2');
            $table->decimal('workforce_trust_score', 5, 2)->nullable()->comment('Composite uy tín tổng hợp');
            $table->unsignedSmallInteger('sandbox_sessions_total')->default(0);
            $table->unsignedSmallInteger('sandbox_hours_total')->default(0);
            $table->decimal('sandbox_score_avg', 5, 2)->nullable();
            $table->timestamp('sandbox_last_completed_at')->nullable();
            $table->unsignedTinyInteger('certifications_count')->default(0);
            $table->string('highest_cert_level', 30)->nullable()->comment('FOUNDATION|PRACTITIONER|PROFESSIONAL|LEADER');
            $table->timestamp('highest_cert_issued_at')->nullable();
            $table->timestamp('highest_cert_expires_at')->nullable();
            $table->decimal('kpi_achievement_avg', 5, 2)->nullable()->comment('% trung bình hoàn thành KPI');
            $table->decimal('impact_score', 5, 2)->nullable()->comment('AI Impact Index tổng hợp');
            $table->string('career_goal', 200)->nullable();
            $table->string('current_learning_path', 100)->nullable();
            $table->unsignedTinyInteger('profile_completeness_pct')->default(0);
            $table->timestamps();
            $table->softDeletes();
            

            // Indexes
            $table->unique(['organization_id', 'user_id'], 'uq_wfp_org_user');
            $table->index('tdwcf_maturity_level', 'idx_wfp_maturity');
            $table->index('ai_readiness_score', 'idx_wfp_ai_readiness');
            $table->index('highest_cert_level', 'idx_wfp_cert_level');
        });

        
    }

    public function down(): void
    {
        Schema::dropIfExists('workforce_profiles');
    }
};