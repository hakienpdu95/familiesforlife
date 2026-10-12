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
        Schema::table('survey_tokens', function (Blueprint $table) {
            if (!Schema::hasColumn('survey_tokens', 'usage_limit')) {
                $table->unsignedInteger('usage_limit')->nullable();
            }
            if (!Schema::hasColumn('survey_tokens', 'usage_count')) {
                $table->unsignedInteger('usage_count')->default(0)->after('usage_limit');
            }
        });
    }

    public function down(): void
    {
        Schema::table('survey_tokens', function (Blueprint $table) {
            $cols = array_filter(['usage_limit', 'usage_count'], fn($c) => Schema::hasColumn('survey_tokens', $c));
            if (!empty($cols)) $table->dropColumn(array_values($cols));
        });
    }
};