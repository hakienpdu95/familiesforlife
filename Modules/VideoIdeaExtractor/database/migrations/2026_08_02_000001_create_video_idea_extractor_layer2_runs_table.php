<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Log riêng cho từng lần chạy "Layer 2" của VideoIdeaExtractor (RunVideoIdeaAiPromptAction) — CHỈ
 * để dashboard Aicem ("Tổng quan") cộng đúng chi phí thật đã dùng, KHÔNG dùng cho việc chặn hạn mức
 * (việc đó đọc/ghi `aicem_monthly_budget_usage` như CoreIdeaExtractor — bảng ngân sách AI DÙNG
 * CHUNG cấp tổ chức/tháng cho MỌI tính năng). Bảng RIÊNG (không tái dùng `cie_layer2_runs` của
 * CoreIdeaExtractor) — module này độc lập hoàn toàn, không nên ghi chéo vào bảng của module khác.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('video_idea_extractor_layer2_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->decimal('cost_usd', 10, 6);
            $table->string('model_used', 100);
            $table->timestamp('created_at')->nullable();

            $table->index(['organization_id', 'created_at'], 'video_idea_extractor_layer2_runs_org_created_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('video_idea_extractor_layer2_runs');
    }
};
