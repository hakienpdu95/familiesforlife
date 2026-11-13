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
        Schema::table('qr_scans', function (Blueprint $table) {
            if (!Schema::hasColumn('qr_scans', 'scan_source')) {
                $table->string('scan_source', 20)->nullable()->default(NULL)->after('user_agent')->comment('Nguồn lượt quét: qr | campaign | internal | referral — trùng giá trị scan_source gửi lên GA');
            }
            if (!Schema::hasColumn('qr_scans', 'locale')) {
                $table->string('locale', 10)->nullable()->default(NULL)->after('scan_source')->comment('Ngôn ngữ trang truy xuất lúc quét');
            }
            if (!Schema::hasIndex('qr_scans', 'idx_qr_scans_time_source')) {
                $table->index(['scanned_at', 'scan_source'], 'idx_qr_scans_time_source');
            }
        });
    }

    public function down(): void
    {
        Schema::table('qr_scans', function (Blueprint $table) {
            if (Schema::hasIndex('qr_scans', 'idx_qr_scans_time_source')) $table->dropIndex('idx_qr_scans_time_source');
            $cols = array_filter(['scan_source', 'locale'], fn($c) => Schema::hasColumn('qr_scans', $c));
            if (!empty($cols)) $table->dropColumn(array_values($cols));
        });
    }
};
