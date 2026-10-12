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
        Schema::table('aicem_generation_runs', function (Blueprint $table) {
            if (!Schema::hasColumn('aicem_generation_runs', 'cache_creation_tokens')) {
                $table->unsignedInteger('cache_creation_tokens')->nullable();
            }
            if (!Schema::hasColumn('aicem_generation_runs', 'cache_read_tokens')) {
                $table->unsignedInteger('cache_read_tokens')->nullable()->after('cache_creation_tokens');
            }
        });
    }

    public function down(): void
    {
        Schema::table('aicem_generation_runs', function (Blueprint $table) {
            $cols = array_filter(['cache_creation_tokens', 'cache_read_tokens'], fn($c) => Schema::hasColumn('aicem_generation_runs', $c));
            if (!empty($cols)) $table->dropColumn(array_values($cols));
        });
    }
};