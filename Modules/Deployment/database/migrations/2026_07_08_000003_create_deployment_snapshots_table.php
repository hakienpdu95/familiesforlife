<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Bỏ cột `snapshot_data` (JSON, spec §4.3 — "toàn bộ tree Blueprint"/"toàn bộ config
     * org tại thời điểm này"):
     *   - snapshot_type='blueprint': blueprint_version đã PUBLISHED nên bất biến
     *     (BlueprintVersionStatus::isImmutable(), Module BusinessBlueprint) — bản thân
     *     blueprint_version_id đã LÀ snapshot, không cần nhân bản dữ liệu ra JSON.
     *   - snapshot_type='organization_config': dùng bảng con
     *     deployment_config_snapshot_items (migration kế tiếp) — ghi lại chính xác những
     *     dòng config nào (theo type+id) đang hiệu lực tại thời điểm deploy, dạng quan hệ
     *     tường minh thay vì JSON blob.
     */
    public function up(): void
    {
        Schema::create('deployment_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->foreignId('deployment_id')->constrained('deployments')->cascadeOnDelete();
            $table->string('snapshot_type', 50); // blueprint|organization_config|runtime_mapping|permission|ai_context
            $table->foreignId('blueprint_version_id')->nullable()->constrained('blueprint_versions')->nullOnDelete();
            $table->timestamp('created_at')->nullable();

            $table->index(['deployment_id', 'snapshot_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('deployment_snapshots');
    }
};
