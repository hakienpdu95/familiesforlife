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
        Schema::table('survey_responses', function (Blueprint $table) {
            if (!Schema::hasIndex('survey_responses', 'survey_responses_survey_respondent_idx')) {
                $table->index(['survey_id', 'respondent_ref'], 'survey_responses_survey_respondent_idx');
            }
        });
    }

    public function down(): void
    {
        Schema::table('survey_responses', function (Blueprint $table) {
            $cols = array_filter([], fn($c) => Schema::hasColumn('survey_responses', $c));
            if (!empty($cols)) $table->dropColumn(array_values($cols));
        });
    }
};