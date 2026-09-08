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
        Schema::table('maturity_levels', function (Blueprint $table) {
            if (!Schema::hasColumn('maturity_levels', 'lead_temperature')) {
                $table->string('lead_temperature', 10)->default('cold')->comment('hot | warm | cold — dùng để classify lead');
            }
        });
    }

    public function down(): void
    {
        Schema::table('maturity_levels', function (Blueprint $table) {
            $cols = array_filter(['lead_temperature'], fn($c) => Schema::hasColumn('maturity_levels', $c));
            if (!empty($cols)) $table->dropColumn(array_values($cols));
        });
    }
};