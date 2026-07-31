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
        });
    }

    public function down(): void
    {
        Schema::table('cie_category_foundations', function (Blueprint $table) {
            $cols = array_filter(['pain_points', 'rejected_ideas', 'writer_insights'], fn($c) => Schema::hasColumn('cie_category_foundations', $c));
            if (!empty($cols)) $table->dropColumn(array_values($cols));
        });
    }
};