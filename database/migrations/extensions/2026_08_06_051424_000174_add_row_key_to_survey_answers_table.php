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
        Schema::table('survey_answers', function (Blueprint $table) {
            if (!Schema::hasColumn('survey_answers', 'row_key')) {
                $table->string('row_key', 100)->nullable();
            }
            if (!Schema::hasIndex('survey_answers', 'sa_response_field_row_idx')) {
                $table->index(['response_id', 'field_id', 'row_key'], 'sa_response_field_row_idx');
            }
        });
    }

    public function down(): void
    {
        Schema::table('survey_answers', function (Blueprint $table) {
            $cols = array_filter(['row_key'], fn($c) => Schema::hasColumn('survey_answers', $c));
            if (!empty($cols)) $table->dropColumn(array_values($cols));
        });
    }
};