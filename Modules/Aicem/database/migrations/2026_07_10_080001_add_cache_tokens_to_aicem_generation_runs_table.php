<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Phase 6 (mục 8.7/15) — lưu số token cache write/read để audit + hiển thị tiết kiệm chi phí. */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('aicem_generation_runs', function (Blueprint $table) {
            if (!Schema::hasColumn('aicem_generation_runs', 'cache_creation_tokens')) {
                $table->unsignedInteger('cache_creation_tokens')->nullable()->after('output_tokens');
            }
            if (!Schema::hasColumn('aicem_generation_runs', 'cache_read_tokens')) {
                $table->unsignedInteger('cache_read_tokens')->nullable()->after('cache_creation_tokens');
            }
        });
    }

    public function down(): void
    {
        Schema::table('aicem_generation_runs', function (Blueprint $table) {
            $cols = array_filter(['cache_creation_tokens', 'cache_read_tokens'], fn ($c) => Schema::hasColumn('aicem_generation_runs', $c));
            if (!empty($cols)) $table->dropColumn(array_values($cols));
        });
    }
};
