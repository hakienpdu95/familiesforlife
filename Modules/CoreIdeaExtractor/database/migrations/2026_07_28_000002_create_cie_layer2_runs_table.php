<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Log riêng cho từng lần chạy "Layer 2" (RunLayer2ExtractionAction) — CHỈ để dashboard Aicem
 * ("Tổng quan") cộng đúng chi phí thật đã dùng, KHÔNG dùng cho việc chặn hạn mức (việc đó vẫn
 * đọc/ghi `aicem_monthly_budget_usage` như cũ qua CheckCoreIdeaAiBudgetAction).
 *
 * Lý do cần bảng riêng thay vì chỉ đọc `aicem_monthly_budget_usage.settled_usd`: cột đó CỘNG
 * DỒN chi phí Layer 2 chung với chi phí các generation run của Aicem (ReconcileBudgetAction) khi
 * organization CÓ đặt hạn mức — không thể tách riêng phần Layer 2 ra khỏi tổng đó để cộng thêm
 * vào `cost_this_month` (vốn đã tính chi phí Aicem trực tiếp từ aicem_generation_runs) mà không
 * bị đếm trùng. Cũng không tái dùng bảng aicem_generation_runs vì subject_id/workflow_id ở đó là
 * NOT NULL + có FK bắt buộc trỏ tới 1 bài viết/sản phẩm + 1 workflow thật — Layer 2 không có khái
 * niệm workflow/subject nào để gán vào cho đúng nghĩa.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('cie_layer2_runs')) {
            return;
        }

        Schema::create('cie_layer2_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->decimal('cost_usd', 10, 6);
            $table->string('model_used', 100);
            $table->timestamp('created_at')->nullable();

            $table->index(['organization_id', 'created_at'], 'cie_layer2_runs_org_created_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cie_layer2_runs');
    }
};
