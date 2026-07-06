<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Bảng mới (không có trong spec gốc) — thay thế `deployment_snapshots.snapshot_data`
     * JSON cho snapshot_type='organization_config'. Mỗi dòng ghi lại 1 config row
     * (theo type + id) đang hiệu lực tại thời điểm deploy — quan hệ tường minh, không
     * cần copy toàn bộ giá trị field (lịch sử field-level đã có Spatie Activitylog
     * trên chính các model organization_*_configs, xem App\Foundation\Models\TenantAwareModel).
     */
    public function up(): void
    {
        Schema::create('deployment_config_snapshot_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->foreignId('deployment_snapshot_id')->constrained('deployment_snapshots')->cascadeOnDelete();
            $table->string('configurable_type', 100); // 'capability_config'|'workflow_config'|'checklist_config'|'role_mapping'|'ai_config'|'resource_override'|'dashboard_widget'
            $table->unsignedBigInteger('configurable_id');
            $table->timestamp('created_at')->nullable();

            $table->index(['deployment_snapshot_id']);
            $table->index(['configurable_type', 'configurable_id'], 'deploy_config_snapshot_items_configurable_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('deployment_config_snapshot_items');
    }
};
