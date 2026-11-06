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
        Schema::table('survey_sections', function (Blueprint $table) {
            if (!Schema::hasColumn('survey_sections', 'section_code')) {
                $table->string('section_code', 50)->nullable()->comment('Code định danh section — dùng cho sectioned aggregation');
            }
            if (!Schema::hasColumn('survey_sections', 'assessment_code')) {
                $table->string('assessment_code', 50)->nullable()->after('section_code')->comment('Gắn section vào assessment — dùng cho sectioned aggregation');
            }
            if (!Schema::hasColumn('survey_sections', 'min_score')) {
                $table->integer('min_score')->default(0)->after('assessment_code')->comment('Raw score thấp nhất lý thuyết (dùng cho normalize)');
            }
            if (!Schema::hasColumn('survey_sections', 'max_score')) {
                $table->integer('max_score')->default(100)->after('min_score')->comment('Raw score cao nhất lý thuyết (dùng cho normalize)');
            }
        });
    }

    public function down(): void
    {
        Schema::table('survey_sections', function (Blueprint $table) {
            $cols = array_filter(['section_code', 'assessment_code', 'min_score', 'max_score'], fn($c) => Schema::hasColumn('survey_sections', $c));
            if (!empty($cols)) $table->dropColumn(array_values($cols));
        });
    }
};