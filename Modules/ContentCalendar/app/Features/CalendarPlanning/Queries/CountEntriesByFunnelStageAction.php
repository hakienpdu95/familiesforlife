<?php

namespace Modules\ContentCalendar\Features\CalendarPlanning\Queries;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\ContentCalendar\Enums\FunnelStage;
use Modules\ContentCalendar\Models\ContentCalendarEntry;
use Modules\Post\Models\PostCategory;

/**
 * (2026-08-11) — đếm entry đang HOẠT ĐỘNG (chưa Done/Dropped — cùng danh sách trạng thái
 * `content_calendar.dedup.active_statuses` đã dùng ở `ListCategoryPlannedTitlesAction`, lý do
 * giống nhau: entry Done/Dropped không còn phản ánh "kế hoạch sắp tới", tính vào sẽ làm sai lệch
 * bức tranh cân bằng Lạnh/Ấm/Nóng hiện tại) theo `funnel_stage` cho 1 category, dùng cho cả UI
 * thanh tỷ lệ (board.blade.php) lẫn `BuildFunnelGapAnalysisPromptAction`.
 *
 * Entry chưa phân loại (`funnel_stage = null`) đếm riêng vào `unclassified` — KHÔNG gộp vào 1
 * trong 3 giai đoạn (sẽ làm sai số phân bổ thật).
 */
class CountEntriesByFunnelStageAction
{
    use AsAction;

    /** @return array{cold: int, warm: int, hot: int, unclassified: int, total: int} */
    public function handle(PostCategory $category): array
    {
        $activeStatuses = config('content_calendar.dedup.active_statuses', ['idea', 'planned', 'drafting', 'blocked', 'ready']);

        $counts = ContentCalendarEntry::query()
            ->where('post_category_id', $category->id)
            ->whereIn('status', $activeStatuses)
            ->selectRaw('funnel_stage, count(*) as total')
            ->groupBy('funnel_stage')
            ->pluck('total', 'funnel_stage');

        $cold = (int) ($counts[FunnelStage::Cold->value] ?? 0);
        $warm = (int) ($counts[FunnelStage::Warm->value] ?? 0);
        $hot = (int) ($counts[FunnelStage::Hot->value] ?? 0);
        // Cột NULL trả về key rỗng "" (không phải 'unclassified') qua pluck() — MySQL/SQLite đều
        // group NULL thành 1 nhóm riêng nhưng khoá là chuỗi rỗng khi ép qua Collection.
        $unclassified = (int) ($counts[''] ?? 0);

        return [
            'cold' => $cold,
            'warm' => $warm,
            'hot' => $hot,
            'unclassified' => $unclassified,
            'total' => $cold + $warm + $hot + $unclassified,
        ];
    }

    /**
     * (2026-08-11) — tính SẴN giai đoạn yếu nhất ở server thay vì để prompt bắt AI tự đoán từ số
     * liệu (AI có thể cộng/so sánh sai) — "thà tính đúng 1 lần ở đây còn hơn để model đoán lại mỗi
     * lần sinh prompt", cùng triết lý `ArticleStructuredDataBuilder::buildOffer()` (thà thiếu còn
     * hơn sai). Dùng cả cho badge cảnh báo trên UI (`funnelGapAnalysis()`) lẫn task #1 của
     * `BuildFunnelGapAnalysisPromptAction`.
     *
     * Chỉ tính trên 3 giai đoạn ĐÃ PHÂN LOẠI (bỏ qua `unclassified` — chưa gán giai đoạn không có
     * nghĩa là "thiếu nội dung giai đoạn đó", chỉ là chưa được gắn nhãn). `< 3` bài đã phân loại
     * thì mẫu quá nhỏ để kết luận gì (null). Ngưỡng 15%: 1 giai đoạn dưới 15% tổng 3 giai đoạn coi
     * là "bị bỏ ngỏ" — đủ chặt để không báo động giả trên phân bổ gần đều (VD 30/35/35%).
     */
    public function describeImbalance(array $counts): ?FunnelStage
    {
        $classified = $counts['cold'] + $counts['warm'] + $counts['hot'];

        if ($classified < 3) {
            return null;
        }

        $byStage = ['cold' => $counts['cold'], 'warm' => $counts['warm'], 'hot' => $counts['hot']];
        $weakestKey = array_search(min($byStage), $byStage, true);
        $weakestShare = $byStage[$weakestKey] / $classified;

        if ($weakestShare >= 0.15) {
            return null;
        }

        return FunnelStage::from($weakestKey);
    }
}
