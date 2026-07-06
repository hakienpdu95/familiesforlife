<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rc_candidates', function (Blueprint $table) {
            $table->date('date_of_birth')->nullable()->after('phone');
            $table->enum('gender', ['male', 'female', 'other'])->nullable()->after('date_of_birth');
            $table->string('current_title', 150)->nullable()->after('gender');
            $table->string('current_company', 150)->nullable()->after('current_title');
            $table->smallInteger('years_experience')->nullable()->after('current_company');
            $table->text('skills')->nullable()->after('years_experience');
            $table->unsignedBigInteger('referred_by')->nullable()->after('source');
            $table->char('mkt_applicant_id', 36)->nullable()->after('referred_by')->index();
            $table->string('status', 20)->default('active')->after('linkedin_url')->index();
            $table->text('blacklist_reason')->nullable()->after('status');
            $table->unsignedBigInteger('created_by')->nullable()->after('blacklist_reason');
            $table->unsignedBigInteger('updated_by')->nullable()->after('created_by');

            $table->index(['org_id', 'status', 'source'], 'idx_rc_cand_status');
        });
    }

    public function down(): void
    {
        Schema::table('rc_candidates', function (Blueprint $table) {
            $table->dropIndex('idx_rc_cand_status');
            $table->dropColumn([
                'date_of_birth', 'gender', 'current_title', 'current_company',
                'years_experience', 'skills', 'referred_by', 'mkt_applicant_id',
                'status', 'blacklist_reason', 'created_by', 'updated_by',
            ]);
        });
    }
};
