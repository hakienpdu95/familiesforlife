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
        Schema::table('ocop_subjects', function (Blueprint $table) {
            if (!Schema::hasColumn('ocop_subjects', 'slug')) {
                $table->string('slug', 255)->nullable();
            }
            if (!Schema::hasColumn('ocop_subjects', 'story')) {
                $table->longText('story')->nullable()->after('slug');
            }
            if (!Schema::hasIndex('ocop_subjects', 'ocop_subjects_slug_unique')) {
                $table->unique('slug');
            }
        });
    }

    public function down(): void
    {
        Schema::table('ocop_subjects', function (Blueprint $table) {
            $cols = array_filter(['slug', 'story'], fn($c) => Schema::hasColumn('ocop_subjects', $c));
            if (!empty($cols)) $table->dropColumn(array_values($cols));
        });
    }
};