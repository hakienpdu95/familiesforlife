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
        Schema::table('cie_category_foundations', function (Blueprint $table) {
            if (!Schema::hasColumn('cie_category_foundations', 'pain_points')) {
                $table->text('pain_points')->nullable();
            }
            if (!Schema::hasColumn('cie_category_foundations', 'rejected_ideas')) {
                $table->text('rejected_ideas')->nullable()->after('pain_points');
            }
            if (!Schema::hasColumn('cie_category_foundations', 'writer_insights')) {
                $table->text('writer_insights')->nullable()->after('rejected_ideas');
            }
            if (!Schema::hasColumn('cie_category_foundations', 'objections')) {
                $table->text('objections')->nullable()->after('writer_insights');
            }
            if (!Schema::hasColumn('cie_category_foundations', 'decision_criteria')) {
                $table->text('decision_criteria')->nullable()->after('objections');
            }
            if (!Schema::hasColumn('cie_category_foundations', 'family_values_focus')) {
                $table->json('family_values_focus')->nullable()->after('decision_criteria');
            }
        });
    }

    public function down(): void
    {
        Schema::table('cie_category_foundations', function (Blueprint $table) {
            $cols = array_filter(['pain_points', 'rejected_ideas', 'writer_insights', 'objections', 'decision_criteria', 'family_values_focus'], fn($c) => Schema::hasColumn('cie_category_foundations', $c));
            if (!empty($cols)) $table->dropColumn(array_values($cols));
        });
    }
};