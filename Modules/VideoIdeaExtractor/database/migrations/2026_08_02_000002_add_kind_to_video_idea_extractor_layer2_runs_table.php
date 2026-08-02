<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Cùng lý do add_kind_to_cie_layer2_runs_table bên CoreIdeaExtractor — module mở rộng thêm 3 tính
 * năng dùng chung bảng audit này với "Layer 2" gốc (kind=layer2): Tiêu đề & Thumbnail
 * (kind=titles), Hook mở đầu (kind=hooks), Ý tưởng Shorts (kind=shorts) — cả 4 đều là 1 lần gọi AI
 * theo prompt build sẵn ở client. `kind` chỉ phục vụ audit/tách chi phí theo tính năng.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('video_idea_extractor_layer2_runs', 'kind')) {
            Schema::table('video_idea_extractor_layer2_runs', function (Blueprint $table) {
                $table->string('kind', 30)->default('layer2')->after('organization_id');
            });
        }
    }

    public function down(): void
    {
        Schema::table('video_idea_extractor_layer2_runs', function (Blueprint $table) {
            $table->dropColumn('kind');
        });
    }
};
