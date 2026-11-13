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
        Schema::table('system_settings', function (Blueprint $table) {
            if (!Schema::hasColumn('system_settings', 'ga_measurement_id')) {
                $table->string('ga_measurement_id', 32)->nullable()->default(NULL)->after('show_portal_locale_switcher')->comment('Measurement ID GA4 (G-XXXXXXX) gắn gtag trên cổng thông tin');
            }
        });
    }

    public function down(): void
    {
        Schema::table('system_settings', function (Blueprint $table) {
            $cols = array_filter(['ga_measurement_id'], fn($c) => Schema::hasColumn('system_settings', $c));
            if (!empty($cols)) $table->dropColumn(array_values($cols));
        });
    }
};
