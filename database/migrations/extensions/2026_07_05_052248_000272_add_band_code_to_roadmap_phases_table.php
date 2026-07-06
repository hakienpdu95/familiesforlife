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
        Schema::table('roadmap_phases', function (Blueprint $table) {
            if (!Schema::hasColumn('roadmap_phases', 'band_code')) {
                $table->string('band_code', 50)->nullable()->comment('Gắn phase theo score_band.band_code — mới theo spec. NULL = dùng maturity_level (cũ)');
            }
        });
    }

    public function down(): void
    {
        Schema::table('roadmap_phases', function (Blueprint $table) {
            $cols = array_filter(['band_code'], fn($c) => Schema::hasColumn('roadmap_phases', $c));
            if (!empty($cols)) $table->dropColumn(array_values($cols));
        });
    }
};