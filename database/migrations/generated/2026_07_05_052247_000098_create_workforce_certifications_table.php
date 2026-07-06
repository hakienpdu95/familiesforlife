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
        if (Schema::hasTable('workforce_certifications')) {
            return;
        }

        Schema::create('workforce_certifications', function (Blueprint $table) {
            $table->id();
            $table->uuid()->nullable()->unique()->comment('Public UUID — expose ra ngoài, không phải PK');
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
            $table->unsignedBigInteger('organization_id');
            $table->unsignedBigInteger('workforce_profile_id');
            $table->unsignedBigInteger('cert_definition_id');
            $table->decimal('assessment_score_at_issue', 5, 2)->nullable()->comment('30%');
            $table->decimal('sandbox_score_at_issue', 5, 2)->nullable()->comment('25%');
            $table->decimal('impact_score_at_issue', 5, 2)->nullable()->comment('25%');
            $table->decimal('portfolio_score_at_issue', 5, 2)->nullable()->comment('20%');
            $table->decimal('composite_score_at_issue', 5, 2)->nullable()->comment('Điểm tổng hợp cuối cùng');
            $table->string('status', 20)->default('active')->comment('active|expired|revoked');
            $table->timestamp('issued_at')->useCurrent();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->text('revoked_reason')->nullable();
            $table->string('certificate_number', 50)->nullable()->unique();
            $table->string('qr_code_url', 500)->nullable();
            $table->string('digital_badge_url', 500)->nullable();
            $table->unsignedBigInteger('issued_by')->nullable();
            $table->unsignedBigInteger('human_reviewer_id')->nullable()->comment('Chuyên gia Human-in-the-Loop');
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
            

            // Indexes
            $table->index(['workforce_profile_id', 'status'], 'idx_wfcert_profile_status');
            $table->index('expires_at', 'idx_wfcert_expires');
        });

        
    }

    public function down(): void
    {
        Schema::dropIfExists('workforce_certifications');
    }
};