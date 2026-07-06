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
        Schema::table('vertical_templates', function (Blueprint $table) {
            if (!Schema::hasColumn('vertical_templates', 'data_collection_template_slug')) {
                $table->string('data_collection_template_slug', 100)->nullable()->comment('slug survey template thu thập dữ liệu thực địa — ví dụ: data_collection_v1');
            }
            if (!Schema::hasColumn('vertical_templates', 'export_config')) {
                $table->json('export_config')->nullable()->after('data_collection_template_slug')->comment('Optional export config JSON (sheets, columns, source mappings) for future vertical-specific export formats');
            }
            if (!Schema::hasColumn('vertical_templates', 'organization_id')) {
                $table->foreignId('organization_id')->nullable()->constrained()->cascadeOnDelete()->after('export_config')->comment('Hợp nhất thư viện + bản instance tổ chức — null = thư viện dùng chung, có giá trị = bản của tổ chức');
            }
            if (!Schema::hasColumn('vertical_templates', 'source_template_id')) {
                $table->foreignId('source_template_id')->nullable()->constrained('vertical_templates')->nullOnDelete()->after('organization_id')->comment('Bản mẫu gốc khi nhân bản — null nếu tự tạo từ đầu');
            }
            if (!Schema::hasColumn('vertical_templates', 'status')) {
                $table->string('status', 20)->default('active')->after('source_template_id')->comment('Thay vai trò bảng organization_verticals (active|inactive)');
            }
            if (!Schema::hasColumn('vertical_templates', 'activated_at')) {
                $table->timestamp('activated_at')->nullable()->after('status')->comment('Thời điểm kích hoạt cho tổ chức');
            }
            if (!Schema::hasColumn('vertical_templates', 'activated_by')) {
                $table->foreignId('activated_by')->nullable()->constrained('users')->nullOnDelete()->after('activated_at')->comment('Người kích hoạt');
            }
            if (!Schema::hasIndex('vertical_templates', 'uq_vertical_template_org_code')) {
                $table->unique(['organization_id', 'code'], 'uq_vertical_template_org_code');
            }
        });
    }

    public function down(): void
    {
        Schema::table('vertical_templates', function (Blueprint $table) {
            if (Schema::hasColumn('vertical_templates', 'organization_id')) $table->dropForeign(['organization_id']);
            if (Schema::hasColumn('vertical_templates', 'source_template_id')) $table->dropForeign(['source_template_id']);
            if (Schema::hasColumn('vertical_templates', 'activated_by')) $table->dropForeign(['activated_by']);
            $cols = array_filter(['data_collection_template_slug', 'export_config', 'organization_id', 'source_template_id', 'status', 'activated_at', 'activated_by'], fn($c) => Schema::hasColumn('vertical_templates', $c));
            if (!empty($cols)) $table->dropColumn(array_values($cols));
        });
    }
};