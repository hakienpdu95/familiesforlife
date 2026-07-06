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
        Schema::table('deployment_issues', function (Blueprint $table) {
            if (!Schema::hasColumn('deployment_issues', 'issue_type')) {
                $table->string('issue_type', 50)->nullable()->comment('Mã loại issue — tra theo vertical_config_items(config_group=issue_type) của đúng vertical_template_id tổ chức, không FK cứng vì danh mục tự tổ chức định nghĩa riêng');
            }
            if (!Schema::hasColumn('deployment_issues', 'severity_detail')) {
                $table->text('severity_detail')->nullable()->after('issue_type')->comment('Mô tả chi tiết mức độ/tình trạng (vd: diện tích nhiễm, biện pháp đã xử lý) — tách khỏi description chung');
            }
        });
    }

    public function down(): void
    {
        Schema::table('deployment_issues', function (Blueprint $table) {
            $cols = array_filter(['issue_type', 'severity_detail'], fn($c) => Schema::hasColumn('deployment_issues', $c));
            if (!empty($cols)) $table->dropColumn(array_values($cols));
        });
    }
};