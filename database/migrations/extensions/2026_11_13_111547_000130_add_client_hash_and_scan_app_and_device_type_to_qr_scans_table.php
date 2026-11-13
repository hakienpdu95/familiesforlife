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
            if (!Schema::hasColumn('qr_scans', 'client_hash')) {
                $table->char('client_hash', 64)->nullable()->default(NULL)->after('locale')->comment('HMAC(IP + User-Agent) — chống đếm trùng khi trình duyệt chặn cookie');
            }
            if (!Schema::hasColumn('qr_scans', 'scan_app')) {
                $table->string('scan_app', 30)->nullable()->default(NULL)->after('client_hash')->comment('Ứng dụng mở trang: zalo | facebook | safari | chrome... (QrScanUserAgent)');
            }
            if (!Schema::hasColumn('qr_scans', 'device_type')) {
                $table->string('device_type', 10)->nullable()->default(NULL)->after('scan_app')->comment('mobile | tablet | desktop');
            }
            if (!Schema::hasColumn('qr_scans', 'os')) {
                $table->string('os', 20)->nullable()->default(NULL)->after('device_type')->comment('ios | android | windows | macos | other');
            }
            if (!Schema::hasColumn('qr_scans', 'os_version')) {
                $table->string('os_version', 10)->nullable()->default(NULL)->after('os')->comment('Phiên bản hệ điều hành chính (17, 14...)');
            }
            if (!Schema::hasColumn('qr_scans', 'device_brand')) {
                $table->string('device_brand', 30)->nullable()->default(NULL)->after('os_version')->comment('Hãng máy suy ra từ User-Agent (apple, samsung, xiaomi...)');
            }
            if (!Schema::hasColumn('qr_scans', 'is_repeat')) {
                $table->boolean('is_repeat')->default(false)->after('device_brand')->comment('Quét lại cùng mã trong DEDUPE_MINUTES bởi cùng khách — vẫn là 1 dòng lịch sử, chỉ đánh dấu');
            }
            if (!Schema::hasIndex('qr_scans', 'idx_qr_scans_identity_client')) {
                $table->index(['qr_identity_id', 'client_hash'], 'idx_qr_scans_identity_client');
            }
            if (!Schema::hasIndex('qr_scans', 'idx_qr_scans_time_app')) {
                $table->index(['scanned_at', 'scan_app'], 'idx_qr_scans_time_app');
            }
            if (!Schema::hasIndex('qr_scans', 'idx_qr_scans_time_repeat')) {
                $table->index(['scanned_at', 'is_repeat'], 'idx_qr_scans_time_repeat');
            }
        });
    }

    public function down(): void
    {
        Schema::table('qr_scans', function (Blueprint $table) {
            foreach (['idx_qr_scans_identity_client', 'idx_qr_scans_time_app', 'idx_qr_scans_time_repeat'] as $index) {
                if (Schema::hasIndex('qr_scans', $index)) $table->dropIndex($index);
            }
            $cols = array_filter(['client_hash', 'scan_app', 'device_type', 'os', 'os_version', 'device_brand', 'is_repeat'], fn($c) => Schema::hasColumn('qr_scans', $c));
            if (!empty($cols)) $table->dropColumn(array_values($cols));
        });
    }
};
