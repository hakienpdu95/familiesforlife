<?php

namespace Modules\ContentCalendar\Features\CalendarPlanning\Actions;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\ContentCalendar\Features\CalendarPlanning\Queries\CountEntriesByFunnelStageAction;
use Modules\ContentFoundation\Models\CategoryContentFoundation;
use Modules\Post\Models\PostCategory;

/**
 * (2026-08-11, đối chiếu 3 nguồn TOFU/MOFU/BOFU — spec/giadinh.md + IdeaDigital/Funnel.io) — dựng
 * 1 prompt "kiểm kê khoảng trống theo giai đoạn hành trình" cho 1 category, mirror đúng khuôn
 * `ContentOutlines\...\BuildContentOutlinePromptAction` (ghép chuỗi thuần, KHÔNG gọi AI Provider,
 * TỰ VIẾT phần resolve foundation thay vì phụ thuộc chéo trait của ContentOutlines/
 * PromptFrameworkStudio — cùng lý do PromptFrameworkStudio's BuildEditorialContextBlockAction đã
 * nêu: phụ thuộc chéo module khoá 2 module vào nhau, phụ thuộc chung config/model của
 * ContentFoundation mới đúng hướng).
 *
 * Khác các Build*PromptAction khác ở chỗ input không phải 1 bài/outline cụ thể mà là TOÀN BỘ
 * category — phản ánh đúng phát hiện chung của cả 3 nguồn: khoảng trống giai đoạn chỉ lộ ra khi
 * nhìn TOÀN CẢNH, không phải từng bài riêng lẻ.
 */
class BuildFunnelGapAnalysisPromptAction
{
    use AsAction;

    public function __construct(
        private readonly CountEntriesByFunnelStageAction $countByStage,
    ) {}

    public function handle(PostCategory $category): string
    {
        $foundation = CategoryContentFoundation::query()
            ->whereHas('categories', fn ($q) => $q->where('post_categories.id', $category->id))
            ->first();

        $counts = $this->countByStage->handle($category);

        $blocks = [];

        $blocks[] = $this->buildEditorialContext($category, $foundation);
        $blocks[] = $this->buildDistribution($counts);
        $blocks[] = $this->buildTask($counts);

        return implode("\n\n", array_filter($blocks, fn ($b) => $b !== ''));
    }

    private function buildEditorialContext(PostCategory $category, ?CategoryContentFoundation $foundation): string
    {
        $lines = [];
        $lines[] = '## Bối cảnh biên tập';
        $lines[] = "Chuyên mục: {$category->name}";

        if (! $foundation) {
            $lines[] = 'Chuyên mục này chưa có "Bối cảnh nội dung" (ContentFoundation) — nhiệm vụ bên dưới sẽ dựa hoàn toàn vào số liệu phân bổ, không có pain point/tiêu chí so sánh cụ thể. Nên bổ sung ContentFoundation trước để kết quả sát hơn.';

            return implode("\n", $lines);
        }

        if ($foundation->audience) {
            $lines[] = "Đối tượng độc giả: {$foundation->audience}";
        }

        if ($foundation->pain_points) {
            $lines[] = "Nỗi đau/băn khoăn (dùng cho giai đoạn Lạnh): {$foundation->pain_points}";
        }

        if ($foundation->objections) {
            $lines[] = "Lăn tăn/phản đối thường gặp (dùng cho giai đoạn Nóng): {$foundation->objections}";
        }

        if ($foundation->decision_criteria) {
            $lines[] = "Tiêu chí ra quyết định (dùng cho giai đoạn Nóng): {$foundation->decision_criteria}";
        }

        return implode("\n", $lines);
    }

    /** @param array{cold: int, warm: int, hot: int, unclassified: int, total: int} $counts */
    private function buildDistribution(array $counts): string
    {
        $lines = [];
        $lines[] = '## Phân bổ nội dung hiện tại theo giai đoạn hành trình';
        $lines[] = 'Đếm trên các kế hoạch đang hoạt động (chưa Đã xong/Đã huỷ) trong chuyên mục này:';
        $lines[] = "- Lạnh (mới biết vấn đề): {$counts['cold']} bài";
        $lines[] = "- Ấm (đang so sánh giải pháp): {$counts['warm']} bài";
        $lines[] = "- Nóng (sẵn sàng quyết định): {$counts['hot']} bài";

        if ($counts['unclassified'] > 0) {
            $lines[] = "- Chưa phân loại giai đoạn: {$counts['unclassified']} bài";
        }

        if ($counts['total'] === 0) {
            $lines[] = 'Chuyên mục này hiện chưa có kế hoạch nào đang hoạt động.';
        }

        return implode("\n", $lines);
    }

    /** @param array{cold: int, warm: int, hot: int, unclassified: int, total: int} $counts */
    private function buildTask(array $counts): string
    {
        $lines = [];
        $lines[] = '## Nhiệm vụ';
        $lines[] = '1. Dựa trên số liệu phân bổ ở trên, xác định giai đoạn (Lạnh/Ấm/Nóng) đang bị bỏ ngỏ nhiều nhất so với 2 giai đoạn còn lại.';
        $lines[] = '2. Giải thích ngắn gọn vì sao mất cân bằng này có thể gây hại — ví dụ dồn hết vào Lạnh thì thu hút được người đọc nhưng không dẫn được ai tới quyết định; dồn hết vào Nóng thì bỏ lỡ phần lớn độc giả còn đang tìm hiểu.';
        $lines[] = '3. Đề xuất 5 ý tưởng bài viết CỤ THỂ cho ĐÚNG giai đoạn đang thiếu, bám sát pain point (nếu là Lạnh) hoặc lăn tăn/tiêu chí so sánh (nếu là Nóng) đã nêu ở bối cảnh biên tập — không đề xuất ý tưởng chung chung không gắn với dữ liệu đã cho.';
        $lines[] = '4. Với mỗi ý tưởng, gợi ý 1 bài ở giai đoạn liền kề (Lạnh→Ấm hoặc Ấm→Nóng) nên liên kết nội bộ tới/từ bài đó, để dẫn dắt người đọc đi tiếp trong hành trình thay vì dừng lại.';
        $lines[] = '5. Trả về dưới dạng bảng: Tiêu đề ý tưởng | Giai đoạn | Pain point/tiêu chí bám theo | Gợi ý liên kết.';

        return implode("\n", $lines);
    }
}
