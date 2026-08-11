<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * (2026-08-11) — xem `Modules\ContentCalendar\Enums\FunnelStage`. Nullable: entry cũ/entry mới
 * tạo chưa phân loại vẫn hợp lệ — không ép biên tập viên phải chọn ngay, đúng tinh thần các field
 * tuỳ chọn khác của bảng này (assigned_to, target_publish_date).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('content_calendar_entries', function (Blueprint $table) {
            $table->string('funnel_stage', 10)->nullable()->after('status');

            // Phục vụ đếm phân bổ theo category (BuildFunnelGapAnalysisPromptAction) — luôn lọc
            // kèm post_category_id nên gộp chung 1 composite index thay vì đơn cột.
            $table->index(['post_category_id', 'funnel_stage'], 'cc_entries_category_funnel_idx');
        });
    }

    public function down(): void
    {
        Schema::table('content_calendar_entries', function (Blueprint $table) {
            $table->dropIndex('cc_entries_category_funnel_idx');
            $table->dropColumn('funnel_stage');
        });
    }
};
