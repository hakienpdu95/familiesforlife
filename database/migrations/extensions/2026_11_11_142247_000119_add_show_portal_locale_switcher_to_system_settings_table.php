<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('system_settings', function (Blueprint $table) {
            if (!Schema::hasColumn('system_settings', 'show_portal_locale_switcher')) {
                $table->boolean('show_portal_locale_switcher')->default(true)->after('time_format')->comment('Hiện nút đổi ngôn ngữ trên header cổng thông tin');
            }
        });
    }

    public function down(): void
    {
        Schema::table('system_settings', function (Blueprint $table) {
            $cols = array_filter(['show_portal_locale_switcher'], fn($c) => Schema::hasColumn('system_settings', $c));
            if (!empty($cols)) $table->dropColumn(array_values($cols));
        });
    }
};
