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
        Schema::table('score_rules', function (Blueprint $table) {
            if (!Schema::hasColumn('score_rules', 'feature_code')) {
                $table->string('feature_code', 100)->nullable()->comment('Định danh đặc trưng Fi — cầu nối với feature_weights. Mặc định = field_key');
            }
            if (!Schema::hasColumn('score_rules', 'question_scoring_type')) {
                $table->string('question_scoring_type', 30)->nullable()->after('feature_code')->comment('none | boolean | single_choice | multi_choice | numeric_range. NULL = dùng condition_type');
            }
            if (!Schema::hasColumn('score_rules', 'min_score_cap')) {
                $table->integer('min_score_cap')->nullable()->after('question_scoring_type')->comment('multi_choice: chặn dưới tổng score');
            }
            if (!Schema::hasColumn('score_rules', 'max_score_cap')) {
                $table->integer('max_score_cap')->nullable()->after('min_score_cap')->comment('multi_choice: chặn trên tổng score');
            }
            if (!Schema::hasColumn('score_rules', 'section_id')) {
                $table->foreignId('section_id')->nullable()->constrained('survey_sections')->nullOnDelete()->after('max_score_cap')->comment('FK -> survey_sections, dùng cho sectioned aggregation');
            }
            if (!Schema::hasIndex('score_rules', 'uq_score_rule_v2')) {
                $table->unique(['assessment_code', 'field_key', 'domain_code', 'condition_type'], 'uq_score_rule_v2');
            }
        });
    }

    public function down(): void
    {
        Schema::table('score_rules', function (Blueprint $table) {
            if (Schema::hasColumn('score_rules', 'section_id')) $table->dropForeign(['section_id']);
            $cols = array_filter(['feature_code', 'question_scoring_type', 'min_score_cap', 'max_score_cap', 'section_id'], fn($c) => Schema::hasColumn('score_rules', $c));
            if (!empty($cols)) $table->dropColumn(array_values($cols));
        });
    }
};