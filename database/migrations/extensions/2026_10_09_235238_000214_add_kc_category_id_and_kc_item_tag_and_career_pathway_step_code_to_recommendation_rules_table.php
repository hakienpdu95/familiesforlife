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
        Schema::table('recommendation_rules', function (Blueprint $table) {
            if (!Schema::hasColumn('recommendation_rules', 'kc_category_id')) {
                $table->unsignedBigInteger('kc_category_id')->nullable();
            }
            if (!Schema::hasColumn('recommendation_rules', 'kc_item_tag')) {
                $table->string('kc_item_tag', 100)->nullable()->after('kc_category_id');
            }
            if (!Schema::hasColumn('recommendation_rules', 'career_pathway_step_code')) {
                $table->string('career_pathway_step_code', 100)->nullable()->after('kc_item_tag');
            }
        });
    }

    public function down(): void
    {
        Schema::table('recommendation_rules', function (Blueprint $table) {
            $cols = array_filter(['kc_category_id', 'kc_item_tag', 'career_pathway_step_code'], fn($c) => Schema::hasColumn('recommendation_rules', $c));
            if (!empty($cols)) $table->dropColumn(array_values($cols));
        });
    }
};