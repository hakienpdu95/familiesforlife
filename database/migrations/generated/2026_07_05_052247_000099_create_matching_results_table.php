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
        if (Schema::hasTable('matching_results')) {
            return;
        }

        Schema::create('matching_results', function (Blueprint $table) {
            $table->id();
            $table->uuid()->nullable()->unique()->comment('Public UUID — expose ra ngoài, không phải PK');
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
            $table->unsignedBigInteger('organization_id');
            $table->unsignedBigInteger('workforce_profile_id');
            $table->unsignedBigInteger('mkt_listing_id');
            $table->unsignedBigInteger('mkt_applicant_id')->nullable();
            $table->decimal('competency_match', 5, 2)->nullable()->comment('40%');
            $table->decimal('certification_match', 5, 2)->nullable()->comment('20%');
            $table->decimal('experience_match', 5, 2)->nullable()->comment('15%');
            $table->decimal('ai_readiness_match', 5, 2)->nullable()->comment('15%');
            $table->decimal('career_goal_match', 5, 2)->nullable()->comment('10%');
            $table->decimal('matching_score', 5, 2)->nullable()->comment('Điểm tổng hợp');
            $table->string('match_level', 20)->nullable()->comment('excellent(90-100)|strong(75-89)|potential(60-74)|development(40-59)|not_recommended(<40)');
            $table->timestamp('calculated_at')->useCurrent();
            $table->string('status', 20)->default('pending')->comment('pending|reviewed|hired|rejected');
            $table->timestamps();
            

            // Indexes
            $table->index(['workforce_profile_id', 'mkt_listing_id'], 'idx_mr_profile_listing');
            $table->index('matching_score', 'idx_mr_score');
        });

        
    }

    public function down(): void
    {
        Schema::dropIfExists('matching_results');
    }
};