<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 2026-07-30 — CoreIdeaExtractor mở rộng thêm "Tóm tắt nội dung" (kind=summarization) và "Tái cấu
 * trúc nội dung" (kind=rewrite), dùng chung bảng audit này với "Layer 2" gốc (kind=layer2) vì cả 3
 * đều là 1 lần gọi AI theo prompt build sẵn ở client, không có workflow_id/subject_id để gán vào
 * aicem_generation_runs (xem docblock đầu file migration tạo bảng). `kind` chỉ phục vụ audit/tách
 * chi phí theo tính năng — GetAicemUsageStatsHandler vẫn SUM(cost_usd) không lọc theo cột này, nên
 * default 'layer2' giữ nguyên ngữ nghĩa dashboard hiện có cho các dòng đã ghi trước đây.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('cie_layer2_runs', 'kind')) {
            Schema::table('cie_layer2_runs', function (Blueprint $table) {
                $table->string('kind', 30)->default('layer2')->after('organization_id');
            });
        }
    }

    public function down(): void
    {
        Schema::table('cie_layer2_runs', function (Blueprint $table) {
            $table->dropColumn('kind');
        });
    }
};
