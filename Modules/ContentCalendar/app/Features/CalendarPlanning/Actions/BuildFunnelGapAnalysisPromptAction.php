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

        $weakest = $this->countByStage->describeImbalance($counts);

        $blocks = [];

        $blocks[] = $this->buildEditorialContext($category, $foundation);
        $blocks[] = $this->buildDistribution($counts, $weakest);
        $blocks[] = $this->buildTask($weakest);

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
    private function buildDistribution(array $counts, ?FunnelStage $weakest): string
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
        } elseif ($weakest) {
            $lines[] = "**Giai đoạn đang bị bỏ ngỏ nhất: {$weakest->label()}** (đã tính sẵn từ số liệu trên — không cần tính lại).";
        } else {
            $lines[] = 'Phân bổ hiện tại tương đối cân bằng giữa 3 giai đoạn (hoặc chưa đủ dữ liệu đã phân loại để kết luận).';
        }

        return implode("\n", $lines);
    }

    private function buildTask(?FunnelStage $weakest): string
    {
        $lines = [];
        $lines[] = '## Nhiệm vụ';

        if (! $weakest) {
            $lines[] = '1. Phân bổ hiện tại đã tương đối cân bằng — đề xuất 2 ý tưởng bài viết mới cho MỖI giai đoạn (Lạnh/Ấm/Nóng) để duy trì, bám sát bối cảnh biên tập ở trên.';
            $lines[] = '2. Với mỗi ý tưởng, gợi ý 1 bài ở giai đoạn liền kề nên liên kết nội bộ tới/từ bài đó.';
            $lines[] = '3. Trả về dưới dạng bảng: Tiêu đề ý tưởng | Giai đoạn | Gợi ý liên kết.';

            return implode("\n", $lines);
        }

        $lines[] = "1. Giải thích ngắn gọn vì sao bỏ ngỏ giai đoạn {$weakest->label()} có thể gây hại cho hành trình đọc — {$weakest->hint()}";
        $lines[] = "2. Đề xuất 5 ý tưởng bài viết CỤ THỂ cho ĐÚNG giai đoạn {$weakest->label()}, bám sát pain point (nếu là Lạnh) hoặc lăn tăn/tiêu chí so sánh (nếu là Nóng) đã nêu ở bối cảnh biên tập — không đề xuất ý tưởng chung chung không gắn với dữ liệu đã cho.";
        $lines[] = '3. Với mỗi ý tưởng, gợi ý 1 bài ở giai đoạn liền kề (Lạnh→Ấm hoặc Ấm→Nóng) nên liên kết nội bộ tới/từ bài đó, để dẫn dắt người đọc đi tiếp trong hành trình thay vì dừng lại.';
        $lines[] = '4. Trả về dưới dạng bảng: Tiêu đề ý tưởng | Pain point/tiêu chí bám theo | Gợi ý liên kết.';

        return implode("\n", $lines);
    }
}
