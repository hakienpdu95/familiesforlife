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
        if (Schema::hasTable('passport_entries')) {
            return;
        }

        Schema::create('passport_entries', function (Blueprint $table) {
            $table->id();
            $table->uuid()->nullable()->unique()->comment('Public UUID — expose ra ngoài, không phải PK');
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('entry_type', 30)->comment('org_tenure | campaign_result | self_declaration');
            $table->unsignedBigInteger('source_org_id')->nullable();
            $table->string('source_org_name', 200)->nullable()->comment('Tên org tại thời điểm snapshot — bất biến');
            $table->string('source_org_logo_path', 500)->nullable();
            $table->timestamp('snapshot_at')->useCurrent();
            $table->date('tenure_start')->nullable();
            $table->date('tenure_end')->nullable();
            $table->unsignedSmallInteger('tenure_months')->nullable();
            $table->string('job_title_at_exit', 200)->nullable();
            $table->string('department_at_exit', 200)->nullable();
            $table->string('role_at_exit', 50)->nullable();
            $table->decimal('tdwcf_score', 5, 2)->nullable();
            $table->string('tdwcf_maturity_level', 64)->nullable();
            $table->decimal('workforce_trust_score', 5, 2)->nullable();
            $table->decimal('ai_readiness_score', 5, 2)->nullable();
            $table->unsignedSmallInteger('sandbox_hours_total')->default(0);
            $table->decimal('sandbox_score_avg', 5, 2)->nullable();
            $table->unsignedTinyInteger('certifications_count')->default(0);
            $table->string('highest_cert_level', 30)->nullable()->comment('FOUNDATION|PRACTITIONER|PROFESSIONAL|LEADER');
            $table->unsignedSmallInteger('impact_entries_count')->default(0);
            $table->string('visibility', 20)->default('private')->comment('private | link_only | public');
            $table->string('share_token', 64)->nullable()->unique();
            $table->timestamp('share_token_expires_at')->nullable();
            $table->tinyInteger('org_verified')->default(0);
            $table->timestamp('org_verified_at')->nullable();
            $table->unsignedBigInteger('org_verified_by_user_id')->nullable();
            $table->timestamp('offboarded_at')->nullable()->comment('Ngày HR action offboard — có thể khác snapshot_at nếu offboard muộn');
            $table->tinyInteger('has_late_offboard_gap')->default(0)->comment('1 nếu có gap giữa ngày nghỉ thực tế và ngày offboard');
            $table->text('personal_note')->nullable();
            $table->timestamps();
            

            // Indexes
            $table->index('user_id', 'pe_user_index');
            $table->index('source_org_id', 'pe_source_org_index');
            $table->index('entry_type', 'pe_type_index');
            $table->index('visibility', 'pe_visibility_index');
            $table->index('snapshot_at', 'pe_snapshot_at_index');
        });

        
    }

    public function down(): void
    {
        Schema::dropIfExists('passport_entries');
    }
};