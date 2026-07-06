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
        Schema::table('rc_candidates', function (Blueprint $table) {
            if (!Schema::hasColumn('rc_candidates', 'date_of_birth')) {
                $table->date('date_of_birth')->nullable();
            }
            if (!Schema::hasColumn('rc_candidates', 'gender')) {
                $table->enum('gender', ['male', 'female', 'other'])->nullable()->after('date_of_birth');
            }
            if (!Schema::hasColumn('rc_candidates', 'current_title')) {
                $table->string('current_title', 150)->nullable()->after('gender');
            }
            if (!Schema::hasColumn('rc_candidates', 'current_company')) {
                $table->string('current_company', 150)->nullable()->after('current_title');
            }
            if (!Schema::hasColumn('rc_candidates', 'years_experience')) {
                $table->smallInteger('years_experience')->nullable()->after('current_company');
            }
            if (!Schema::hasColumn('rc_candidates', 'skills')) {
                $table->text('skills')->nullable()->after('years_experience');
            }
            if (!Schema::hasColumn('rc_candidates', 'referred_by')) {
                $table->unsignedBigInteger('referred_by')->nullable()->after('skills');
            }
            if (!Schema::hasColumn('rc_candidates', 'mkt_applicant_id')) {
                $table->char('mkt_applicant_id', 36)->nullable()->index()->after('referred_by');
            }
            if (!Schema::hasColumn('rc_candidates', 'status')) {
                $table->string('status', 20)->default('active')->index()->after('mkt_applicant_id');
            }
            if (!Schema::hasColumn('rc_candidates', 'blacklist_reason')) {
                $table->text('blacklist_reason')->nullable()->after('status');
            }
            if (!Schema::hasColumn('rc_candidates', 'created_by')) {
                $table->unsignedBigInteger('created_by')->nullable()->after('blacklist_reason');
            }
            if (!Schema::hasColumn('rc_candidates', 'updated_by')) {
                $table->unsignedBigInteger('updated_by')->nullable()->after('created_by');
            }
            if (!Schema::hasIndex('rc_candidates', 'idx_rc_cand_status')) {
                $table->index(['org_id', 'status', 'source'], 'idx_rc_cand_status');
            }
        });
    }

    public function down(): void
    {
        Schema::table('rc_candidates', function (Blueprint $table) {
            $cols = array_filter(['date_of_birth', 'gender', 'current_title', 'current_company', 'years_experience', 'skills', 'referred_by', 'mkt_applicant_id', 'status', 'blacklist_reason', 'created_by', 'updated_by'], fn($c) => Schema::hasColumn('rc_candidates', $c));
            if (!empty($cols)) $table->dropColumn(array_values($cols));
        });
    }
};