<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('surveys', function (Blueprint $table) {
            if (!Schema::hasColumn('surveys', 'assessment_code')) {
                $table->string('assessment_code', 50)->nullable()->comment('Mã định danh cho Scoring Engine — nullable nếu survey không có scoring');
            }
            if (!Schema::hasColumn('surveys', 'lead_notify_email')) {
                $table->string('lead_notify_email', 255)->nullable()->after('assessment_code')->comment('Email nhận alert khi hot lead nộp bài — để trống = không gửi');
            }
            if (!Schema::hasColumn('surveys', 'allow_multiple_responses')) {
                $table->boolean('allow_multiple_responses')->default(true)->after('lead_notify_email');
            }
            if (!Schema::hasColumn('surveys', 'organization_id')) {
                $table->foreignId('organization_id')->nullable()->constrained()->restrictOnDelete()->after('allow_multiple_responses');
            }
            if (!Schema::hasColumn('surveys', 'specialized_set_code')) {
                $table->string('specialized_set_code', 50)->nullable()->after('organization_id');
            }
            if (!Schema::hasIndex('surveys', 'surveys_assessment_specialized_unique')) {
                $table->unique(['assessment_code', 'specialized_set_code'], 'surveys_assessment_specialized_unique');
            }
            if (!Schema::hasColumn('surveys', 'description')) {
                $table->text('description')->nullable()->after('specialized_set_code');
            }
            if (!Schema::hasColumn('surveys', 'turnstile_site_id')) {
                $table->foreignId('turnstile_site_id')->nullable()->constrained('survey_turnstile_sites')->nullOnDelete()->after('description');
            }
        });
    }

    public function down(): void
    {
        Schema::table('surveys', function (Blueprint $table) {
            if (Schema::hasColumn('surveys', 'organization_id')) $table->dropForeign(['organization_id']);
            if (Schema::hasColumn('surveys', 'turnstile_site_id')) $table->dropForeign(['turnstile_site_id']);
            $cols = array_filter(['assessment_code', 'lead_notify_email', 'allow_multiple_responses', 'organization_id', 'specialized_set_code', 'description', 'turnstile_site_id'], fn($c) => Schema::hasColumn('surveys', $c));
            if (!empty($cols)) $table->dropColumn(array_values($cols));
        });
    }
};