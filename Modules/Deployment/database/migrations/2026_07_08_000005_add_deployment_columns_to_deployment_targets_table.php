<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Trường hợp 3 (docs/migration-guide.md) — ALTER trực tiếp trong Module theo chỉ định
 * của người dùng, sẽ chạy `php artisan migration:sync` sau để đồng bộ ngược vào
 * render_extension_file.json (bước 3-4 của quy trình "Trường hợp 3").
 *
 * Pin Runtime vào đúng Blueprint Version đã deploy (spec §4.3, RR-002 A05, BR-010 A04.3).
 * `vertical_code` (cột cũ) GIỮ NGUYÊN trong giai đoạn chuyển tiếp — không xoá ngay
 * (spec §4.6, tương thích ngược với VerticalRegistry::resolveForOrganization()).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('deployment_targets', function (Blueprint $table) {
            $table->foreignId('deployment_id')->nullable()->after('id')->constrained('deployments')->nullOnDelete();
            $table->foreignId('blueprint_version_id')->nullable()->after('deployment_id')->constrained('blueprint_versions')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('deployment_targets', function (Blueprint $table) {
            $table->dropConstrainedForeignId('blueprint_version_id');
            $table->dropConstrainedForeignId('deployment_id');
        });
    }
};
