<?php

namespace Modules\ContentOutlines\Features\OutlineGeneration\Actions;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\ContentFoundation\Models\CategoryContentFoundation;
use Modules\ContentOutlines\Features\OutlineGeneration\Data\ContentOutlineInputData;

/**
 * spec/ContentOutlines_Technical_Specification.md §3 — sinh prompt Markdown TOP → MIDDLE → BOTTOM
 * (cùng quy ước CoreIdeaExtractor::buildLayer2PromptText()). KHÔNG gọi AI Provider nào (§0 mục 1)
 * — chỉ trả về chuỗi text, người dùng tự copy sang AI ngoài.
 *
 * §4.1 (v1.1) — rủi ro "prompt phình to" (CategoryContentFoundation đầy đủ + competitor_urls dài
 * + additional_notes dài dễ vượt 8-12k token, một số AI ngoài cắt/giảm chất lượng). Giảm bằng 2
 * cơ chế: (1) `outline_depth` cắt ngắn field foundation + giới hạn số URL tham khảo + rút gọn/mở
 * rộng BOTTOM theo lựa chọn của người dùng; (2) `estimateWordCount()` cho phép cảnh báo NGƯỜI
 * DÙNG (UI) khi prompt cuối cùng vượt ngưỡng, KHÔNG tự cắt ngầm sau khi đã áp outline_depth (im
 * lặng cắt thêm sẽ khiến người dùng không hiểu vì sao 1 phần thông tin họ nhập biến mất).
 */
class BuildContentOutlinePromptAction
{
    use AsAction;

    /** §4.1 (v1.1) — ngưỡng cảnh báo "soft warning" ở UI (show.blade.php), KHÔNG chặn tạo/sinh lại. */
    public const WORD_COUNT_WARNING_THRESHOLD = 6000;

    private const SEARCH_INTENT_LABELS = [
        'informational' => 'Học/tìm hiểu thông tin (Informational)',
        'commercial' => 'So sánh/đánh giá lựa chọn (Commercial investigation)',
        'transactional' => 'Sẵn sàng hành động/mua (Transactional)',
        'navigational' => 'Tìm 1 trang/thương hiệu cụ thể (Navigational)',
        'comparison' => 'So sánh trực tiếp A vs B (Comparison)',
    ];

    /** §4.1 (v1.1) — cắt ngắn TỪNG field foundation (core_focus/pain_points/...) theo outline_depth. */
    private const FOUNDATION_FIELD_CHAR_LIMITS = [
        'brief' => 300,
        'standard' => 800,
        'detailed' => null, // không giới hạn — foundation tự giới hạn 2000 ký tự/field ở nguồn (ContentFoundation)
    ];

    /** §4.1 (v1.1) — giới hạn SỐ DÒNG competitor_urls đưa vào prompt theo outline_depth. */
    private const COMPETITOR_URL_LIMITS = [
        'brief' => 3,
        'standard' => 8,
        'detailed' => 20,
    ];

    /**
     * §4.6 (v1.2, tham khảo piperocket.digital/checklists/content-marketing-checklist —
     * "build an internal-linking plan that connects new content to existing pages") — giới hạn
     * số tiêu đề bài ĐÃ PUBLISH đưa vào prompt theo outline_depth, cùng nguyên tắc
     * COMPETITOR_URL_LIMITS (không để 1 category nhiều bài làm phình prompt).
     */
    private const EXISTING_ARTICLE_TITLE_LIMITS = [
        'brief' => 5,
        'standard' => 10,
        'detailed' => 20,
    ];

    /**
     * @param  string[]  $existingArticleTitles  §4.6 (v1.2) — tiêu đề bài ĐÃ PUBLISH cùng chuyên
     *                                           mục (đã tra qua ResolvesCategoryContext::resolveExistingArticleTitles() TRƯỚC khi gọi vào
     *                                           đây, cùng nguyên tắc $foundation — Action không tự query) — dùng để gợi ý internal link
     *                                           THẬT + tránh đề xuất trùng chủ đề đã viết. Rỗng nếu không chọn category hoặc category
     *                                           chưa có bài publish nào.
     */
    public function handle(ContentOutlineInputData $input, ?CategoryContentFoundation $foundation, array $existingArticleTitles = []): string
    {
        $depth = in_array($input->outline_depth, ['brief', 'standard', 'detailed'], true) ? $input->outline_depth : 'standard';

        $lines = [];

        $lines[] = $this->buildTop($input, $foundation, $depth);
        $lines[] = $this->buildMiddle($foundation, $existingArticleTitles, $depth);
        $lines[] = $this->buildBottom($depth, $input->content_role, $existingArticleTitles !== []);

        return implode("\n\n", array_filter($lines, fn ($block) => trim($block) !== ''));
    }

    /**
     * §4.1 (v1.1) — ước lượng số từ để UI cảnh báo (show.blade.php + form trước khi submit qua
     * JS, xem content-outlines.js). Tách khoảng trắng theo Unicode (`\s+`) — KHÔNG dùng
     * str_word_count() (chỉ nhận ký tự ASCII, đếm sai với tiếng Việt có dấu).
     */
    public static function estimateWordCount(string $text): int
    {
        $trimmed = trim($text);
        if ($trimmed === '') {
            return 0;
        }

        return count(preg_split('/\s+/u', $trimmed) ?: []);
    }

    private function buildTop(ContentOutlineInputData $input, ?CategoryContentFoundation $foundation, string $depth): string
    {
        $rows = [];
        $rows[] = "- **Chủ đề:** {$input->topic}";
        $rows[] = "- **Từ khoá mục tiêu:** {$input->target_keyword}";

        if (! empty($input->secondary_keywords)) {
            $rows[] = "- **Từ khoá phụ/liên quan:** {$input->secondary_keywords}";
        }

        $rows[] = '- **Ý định tìm kiếm:** '.$this->resolveSearchIntentLine($input->search_intent);

        $audience = $input->target_audience ?: $foundation?->audience;
        if (! empty($audience)) {
            $rows[] = "- **Đối tượng độc giả:** {$audience}";
        }

        $goal = $input->content_goal ?: $foundation?->content_goals;
        if (! empty($goal)) {
            $rows[] = "- **Mục tiêu bài viết:** {$goal}";
        }

        $tone = $input->tone_style ?: $foundation?->style_sample;
        if (! empty($tone)) {
            $rows[] = "- **Giọng văn:** {$tone}";
        }

        $rows[] = '- **Số từ mong muốn:** '.($input->desired_word_count
            ? "khoảng {$input->desired_word_count} từ"
            : 'không giới hạn cứng — bạn tự đề xuất độ dài hợp lý theo độ phức tạp chủ đề');

        $rows[] = '- **Ngôn ngữ đầu ra:** '.($input->language === 'en' ? 'English' : 'Tiếng Việt');

        if ($input->content_role !== null) {
            $rows[] = '- **Vai trò nội dung:** '.($input->content_role === 'pillar'
                ? 'Trụ cột (pillar) — bài tổng quan, là trung tâm liên kết TỚI các bài cụm hẹp hơn'
                : 'Cụm (cluster) — bài hẹp, tập trung 1 câu hỏi cụ thể, nên liên kết LÊN 1 bài tổng quan rộng hơn nếu có');
        }

        if (! empty($input->competitor_urls)) {
            $allUrls = array_filter(array_map('trim', explode("\n", $input->competitor_urls)));
            $limit = self::COMPETITOR_URL_LIMITS[$depth];
            $urls = array_slice($allUrls, 0, $limit);

            if ($urls !== []) {
                $rows[] = '- **Nguồn/đối thủ tham khảo:**';
                foreach ($urls as $url) {
                    $rows[] = "  - {$url}";
                }

                $omitted = count($allUrls) - count($urls);
                if ($omitted > 0) {
                    $rows[] = "  (còn {$omitted} nguồn khác không đưa vào — outline_depth=\"{$depth}\" giới hạn tối đa {$limit} nguồn, chọn \"detailed\" để đưa nhiều hơn.)";
                }

                $rows[] = '  (Nếu công cụ của bạn không truy cập được các URL này, hãy research chủ đề tương tự qua tri thức/khả năng tìm kiếm của bạn.)';
            }
        }

        if (! empty($input->additional_notes)) {
            $rows[] = "- **Ghi chú thêm:** {$input->additional_notes}";
        }

        return "Bạn là chuyên gia SEO Content Strategist giàu kinh nghiệm, có khả năng research SERP thực tế (dùng web search nếu công cụ của bạn hỗ trợ) và tổng hợp thành content outline chi tiết, sẵn sàng để biên tập viên triển khai thành bài viết hoàn chỉnh.\n\n".
            "## Thông tin đầu vào\n".implode("\n", $rows);
    }

    private function resolveSearchIntentLine(?string $searchIntent): string
    {
        if ($searchIntent === null) {
            return 'chưa xác định — bạn tự xác định qua research và NÊU RÕ lựa chọn ở đầu outline';
        }

        return self::SEARCH_INTENT_LABELS[$searchIntent] ?? $searchIntent;
    }

    private function buildMiddle(?CategoryContentFoundation $foundation, array $existingArticleTitles, string $depth): string
    {
        $blocks = [];
        $charLimit = self::FOUNDATION_FIELD_CHAR_LIMITS[$depth];

        if ($foundation) {
            $context = [];

            $coreFocus = $this->truncate($foundation->core_focus, $charLimit);
            if (! empty($coreFocus)) {
                $context[] = "- **Trọng tâm chuyên mục:** {$coreFocus}";
            }

            $uniqueAngle = $this->truncate($foundation->unique_angle, $charLimit);
            if (! empty($uniqueAngle)) {
                $context[] = "- **Góc nhìn riêng của chuyên mục:** {$uniqueAngle}";
            }

            $painPoints = $this->truncate($foundation->pain_points, $charLimit);
            if (! empty($painPoints)) {
                $context[] = "- **Khó khăn/câu hỏi thực tế của độc giả:** {$painPoints} — ưu tiên định dạng hướng dẫn/checklist cho phần chạm tới các khó khăn này.";
            }

            $objections = $this->truncate($foundation->objections, $charLimit);
            if (! empty($objections)) {
                $context[] = "- **Lý do độc giả còn nghi ngờ/chưa tin:** {$objections} — đưa các nghi ngờ này vào khối FAQ dưới dạng bóc trần ngộ nhận.";
            }

            $decisionCriteria = $this->truncate($foundation->decision_criteria, $charLimit);
            if (! empty($decisionCriteria)) {
                $context[] = "- **Tiêu chí độc giả dùng để so sánh/lựa chọn:** {$decisionCriteria} — 1 phần so sánh/tiêu chí lựa chọn nên phản ánh đúng các tiêu chí này.";
            }

            $rejectedIdeas = $this->truncate($foundation->rejected_ideas, $charLimit);
            if (! empty($rejectedIdeas)) {
                $context[] = "- **Các góc độ đã dùng, KHÔNG lặp lại:** {$rejectedIdeas}";
            }

            if ($context !== []) {
                $blocks[] = "## Ngữ cảnh chuyên mục\n".implode("\n", $context);
            }
        }

        $blocks[] = $this->buildExistingArticlesBlock($existingArticleTitles, $depth);
        $blocks[] = $this->buildFamilyValuesBlock($foundation);

        return implode("\n\n", array_filter($blocks, fn ($b) => trim($b) !== ''));
    }

    /**
     * §4.6 (v1.2) — internal link THẬT: cho AI danh sách tiêu đề bài đã publish cùng chuyên mục
     * để (a) gợi ý internal link cụ thể hơn "loại nguồn chung" (khác BƯỚC internal/external link
     * ở BOTTOM, vẫn giữ nguyên KHÔNG bịa URL — model chỉ biết TÊN bài, không biết URL thật) và
     * (b) tránh đề xuất outline trùng chủ đề đã có, bổ sung cho rejected_ideas (khác nguồn: đây
     * là bài THẬT đã xuất bản, rejected_ideas là ý đã bị LOẠI trước khi viết).
     */
    private function buildExistingArticlesBlock(array $existingArticleTitles, string $depth): string
    {
        if ($existingArticleTitles === []) {
            return '';
        }

        $limit = self::EXISTING_ARTICLE_TITLE_LIMITS[$depth];
        $titles = array_slice($existingArticleTitles, 0, $limit);

        $lines = [];
        $lines[] = '## Bài viết đã có trong chuyên mục';
        $lines[] = 'Dùng để (1) gợi ý internal link CỤ THỂ hơn ở bước internal/external link (nêu TÊN bài phù hợp, KHÔNG bịa URL — biên tập viên tự tìm đúng URL), (2) tránh dựng outline trùng chủ đề đã viết:';
        foreach ($titles as $title) {
            $lines[] = "- {$title}";
        }

        $omitted = count($existingArticleTitles) - count($titles);
        if ($omitted > 0) {
            $lines[] = "(còn {$omitted} bài khác không đưa vào — outline_depth=\"{$depth}\" giới hạn tối đa {$limit} bài.)";
        }

        return implode("\n", $lines);
    }

    /** §4.1 (v1.1) — cắt ngắn theo KÝ TỰ (mb_substr, an toàn với Unicode) — null = không cắt. */
    private function truncate(?string $text, ?int $charLimit): ?string
    {
        if ($text === null || $charLimit === null || mb_strlen($text) <= $charLimit) {
            return $text;
        }

        return mb_substr($text, 0, $charLimit).'…';
    }

    /**
     * spec/ContentOutlines_Technical_Specification.md §3.2 — khối "Hệ giá trị gia đình Việt Nam"
     * CỐ ĐỊNH, LUÔN chèn bất kể có chọn category hay không — đúng quy ước CoreIdeaExtractor
     * §12.10. Nguồn SỰ THẬT DUY NHẤT là config('content_foundation.family_values'), KHÔNG hardcode
     * lặp lại nội dung ở đây. KHÔNG cắt theo outline_depth — đây là chuẩn nền tảng, không phải
     * ngữ cảnh biên tập tự viết có thể rút gọn.
     */
    private function buildFamilyValuesBlock(?CategoryContentFoundation $foundation): string
    {
        $familyValues = config('content_foundation.family_values');
        $items = $familyValues['items'] ?? [];

        if ($items === []) {
            return '';
        }

        $lines = [];
        $lines[] = '## Hệ giá trị gia đình Việt Nam (chuẩn nền tảng)';
        $lines[] = "Nội dung phải PHỤC VỤ (không đi ngược lại) 4 giá trị sau — {$familyValues['decision_ref']}:";

        foreach ($items as $item) {
            $lines[] = "- **{$item['label']}:** {$item['description']}";
        }

        $lines[] = 'KHÔNG cổ suý bất bình đẳng giới, bạo lực gia đình, hủ tục lạc hậu, hoặc lối sống thiếu chuẩn mực.';

        $focusKeys = $foundation?->family_values_focus ?? [];
        if ($focusKeys !== []) {
            $focusLabels = collect($items)
                ->whereIn('key', $focusKeys)
                ->pluck('label')
                ->implode(', ');

            if ($focusLabels !== '') {
                $lines[] = "**Ưu tiên bổ sung cho chuyên mục này:** {$focusLabels}.";
            }
        }

        return implode("\n", $lines);
    }

    /**
     * §4.9 (v1.6, tham khảo umbrex.com/.../content-pillars-topic-cluster-framework) — chèn ghi
     * chú định hướng CHIỀU internal link theo `content_role` vào placeholder {{ROLE_LINK_NOTE}}
     * đặt sẵn trong 2 biến thể standard/detailed (KHÔNG có ở brief — brief không có bước
     * internal/external link nào để gắn vào, giữ đúng tinh thần rút gọn §4.1).
     */
    private function buildBottom(string $depth, ?string $contentRole, bool $hasExistingArticles): string
    {
        $template = match ($depth) {
            'brief' => $this->buildBottomBrief(),
            'detailed' => $this->buildBottomDetailed(),
            default => $this->buildBottomStandard(),
        };

        return str_replace('{{ROLE_LINK_NOTE}}', $this->buildRoleLinkNote($contentRole, $hasExistingArticles), $template);
    }

    /**
     * §4.9 (v1.6) — mô hình Pillar↔Cluster: Trụ cột là hub liên kết TỚI các cụm (child links);
     * Cụm liên kết LÊN trụ cột (parent link) + NGANG sang cụm liên quan (sibling links). Rỗng
     * nếu `content_role` chưa xác định — KHÔNG thay đổi hành vi mặc định (backward-compatible).
     */
    private function buildRoleLinkNote(?string $contentRole, bool $hasExistingArticles): string
    {
        if ($contentRole === 'pillar') {
            return $hasExistingArticles
                ? ' **Vai trò TRỤ CỘT:** cấu trúc bài như 1 hub tổng quan — mỗi H2 nên tương ứng 1 chủ đề hẹp hơn có thể tách thành 1 bài cụm riêng; nếu có bài phù hợp trong "Bài viết đã có trong chuyên mục" (ở trên) làm cụm cho 1 H2, đề xuất link TỪ đây TỚI đúng bài đó ở H2 tương ứng.'
                : ' **Vai trò TRỤ CỘT:** cấu trúc bài như 1 hub tổng quan — mỗi H2 nên tương ứng 1 chủ đề hẹp hơn có thể tách thành 1 bài cụm riêng để viết sau (chuyên mục chưa có bài nào để link tới lúc này).';
        }

        if ($contentRole === 'cluster') {
            return $hasExistingArticles
                ? ' **Vai trò CỤM:** giữ bài này HẸP, tập trung 1 câu hỏi cụ thể — không cố bao quát toàn bộ chủ đề rộng; nếu có bài tổng quan rộng hơn trong "Bài viết đã có trong chuyên mục" (ở trên), đề xuất link LÊN bài đó; nếu có bài cụm khác liên quan, đề xuất link NGANG (sibling) tới bài đó.'
                : ' **Vai trò CỤM:** giữ bài này HẸP, tập trung 1 câu hỏi cụ thể — không cố bao quát toàn bộ chủ đề rộng (chuyên mục chưa có bài nào để link lên/ngang lúc này).';
        }

        return '';
    }

    /**
     * §4.11 (v1.8, tham khảo advancedwebranking.com/blog/ai-generated-content-prompts-framework) —
     * 4 tinh chỉnh áp DÙNG CHUNG cho cả 3 biến thể (brief/standard/detailed), không riêng biến thể
     * này: (1) bước H2 thêm quy tắc "answer-first" (mỗi heading mở đầu bằng câu trả lời trực tiếp
     * trước khi giải thích — tối ưu cho featured snippet/AI answer engine); (2) bước research thêm
     * lưu ý research CẢ cách AI answer engine (AI Overview/ChatGPT/Gemini) đang trả lời câu hỏi,
     * không chỉ SERP cổ điển; (3) ghi chú EEAT/độ tin cậy dữ liệu thêm 1 câu chặn bịa số liệu
     * (yêu cầu ghi "[cần biên tập viên xác minh]" khi không chắc, thay vì tạo số liệu giả); (4) bước
     * làm rõ nội dung heading (standard/detailed) thêm yêu cầu danh sách/bullet phải có câu dẫn
     * nhập ngữ cảnh, và bước ước lượng số từ (detailed) thêm sai số ±10% + tính cả phần mở đầu.
     * KHÔNG áp dụng: kiến trúc 3-giai đoạn có approval gate, tổng hợp 7 loại tài liệu tham khảo,
     * so sánh/hợp nhất đa AI model, phân loại entity Essential/Optional — đều đòi hỏi gọi AI Provider
     * trong app hoặc research đa vòng có xét duyệt người dùng, ngoài phạm vi đã chốt ở §0 mục 1
     * (KHÔNG gọi AI Provider nào trong app) và mục 3 (không tự research/crawl SERP hay fetch URL
     * đối thủ) — công cụ này chỉ SINH RA 1 prompt duy nhất, người dùng tự chạy ở AI ngoài.
     *
     * §4.12 (v1.9, tham khảo aiexecutionhub.com/blog/ai-blog-post-outlining-system) — 6 tinh
     * chỉnh: (1) bước mới "Chọn kiểu bài (structure archetype)" — 4 kiểu (Hướng dẫn tuần tự/
     * Framework/So sánh-kết luận/Danh sách tài nguyên), CHỈ `standard`/`detailed` (đặt trước bước
     * dựng H2/H3, kèm mục output `## Kiểu bài`); (2) bước "Xác nhận ý định tìm kiếm" (`standard`/
     * `detailed`) mở rộng thành 1 đoạn trả lời 3 câu hỏi (đọc xong làm được gì/biết gì đầu-cuối/vì
     * sao bỏ tab) thay vì chỉ xác nhận nhãn ý định; (3) bước dựng H2/H3 (`standard`/`detailed`)
     * thêm quy tắc "Content H3, không phải Label H3" (H3 phải nêu 1 điểm cụ thể, đọc riêng tiêu đề
     * cũng hiểu, không đặt nhãn chung như "Ví dụ"/"Lưu ý") + ngưỡng H2 >400 từ nên có ≥2 H3; (4)
     * bước làm rõ nội dung heading (`standard`/`detailed`) thêm 1 câu "differentiation note" mỗi
     * H2 — phần này khác gì bài đối thủ điển hình; (5) bước FAQ (CẢ 3 depth) thêm yêu cầu câu hỏi
     * PAA phải là THẬT quan sát được ở bước research, không tự bịa; (6) bước internal/external link
     * (`standard`/`detailed`) thêm yêu cầu kèm gợi ý anchor text ngắn cho mỗi internal link. KHÔNG
     * áp dụng: kiến trúc 4-bước tách rời có gọi AI riêng từng bước (Intent→Structure→Briefs→H3,
     * đòi hỏi nhiều lượt gọi AI Provider trong app + lưu trạng thái giữa các bước, ngoài phạm vi
     * §0) — 6 điểm trên được gộp CHUNG vào 1 prompt duy nhất, giữ đúng mô hình "sinh 1 lần" đã
     * chốt; "word target mỗi section" cũng KHÔNG thêm bước mới vì đã có bước "Ước lượng số từ mỗi
     * phần" (detailed, §4.11) — không cần trùng lặp.
     *
     * §4.13 (v1.10, tham khảo tangence.in/blog/seo-content-creation) — 2 tinh chỉnh, phần còn lại
     * của nguồn (topic cluster, tools Ahrefs/SEMrush/Surfer, lịch cập nhật content 6-12 tháng,
     * readability tool cụ thể) đều đã có cơ chế tương đương (`content_role` §4.9 cho topic cluster)
     * hoặc ngoài phạm vi (§0 không tích hợp tool ngoài/không audit hậu-publish): (1) bước Meta
     * (CẢ 3 depth) thêm gợi ý loại Schema markup (Article/BlogPosting mặc định; +FAQPage nếu có
     * khối FAQ; +HowTo/+ItemList theo `structure archetype` đã chọn ở §4.12, CHỈ `standard`/
     * `detailed` vì `brief` không có bước đó — dùng heuristic đơn giản hơn); (2) bước làm rõ nội
     * dung heading (`standard`/`detailed`) thêm yêu cầu alt text ngắn cho MỖI hình ảnh được gợi ý
     * — cả 2 điểm chỉ GỢI Ý (loại schema/alt text), không sinh JSON-LD hay ảnh thật.
     *
     * §4.14 (v1.11, tham khảo moodymedia.io/blog/how-to-write-for-seo) — 4 tinh chỉnh on-page CẢ 3
     * depth, phần còn lại của nguồn (viết cho người đọc trước, PAA/H2-H3 đối thủ trước khi viết,
     * EEAT, ngắn gọn/bullet, external link, AI chỉ là trợ lý nghiên cứu) đã có cơ chế tương đương
     * từ v1.0-v1.10: (1) từ khoá mục tiêu ĐẶT GẦN ĐẦU (không chỉ "chứa") ở CẢ tiêu đề H1, Meta
     * Title, Meta Description; (2) Meta Description đổi ngưỡng "≤155" → khoảng "140-160" ký tự +
     * yêu cầu câu chủ động + 1 lời mời hành động ngắn (tăng CTR từ SERP — Meta vẫn đặt SAU bước
     * dựng H2/H3+heading detail trong quy trình, giữ đúng nguyên tắc "viết Meta sau khi có nội
     * dung chính" của nguồn, không cần đổi thứ tự bước); (3) bước thesis thêm yêu cầu đặt từ khoá
     * mục tiêu tự nhiên trong 100-150 từ đầu bài (ngay sau H1); (4) thêm ghi chú chung "Mật độ từ
     * khoá" vào dòng Lưu ý EEAT — không có ngưỡng % bắt buộc, tránh nhồi từ khoá — gộp chung
     * nguyên tắc "không nhồi từ khoá" đã áp dụng riêng lẻ cho alt text (§4.13)/anchor text (§4.12)
     * thành 1 quy tắc tổng quát cho TOÀN BÀI.
     *
     * §4.1 (v1.1) — rút gọn: 5 bước, FAQ 3 câu, bỏ bước tự rà lại riêng (gộp vào bước cuối).
     */
    private function buildBottomBrief(): string
    {
        return <<<'MARKDOWN'
## Quy trình thực hiện (rút gọn)

1. **Research nhanh** — dựa vào tri thức/khả năng tìm kiếm của bạn + nguồn tham khảo ở trên (nếu có), nắm nội dung đang xếp hạng tốt cho từ khoá mục tiêu đang thiếu/lỗi thời gì; nếu ước tính được, ghi chú ngắn khối lượng tìm kiếm hàng tháng + độ khó từ khoá.
2. **Xác nhận ý định tìm kiếm + USP + đề xuất 1-2 phương án tiêu đề (H1)** — USP: 1 câu CỤ THỂ vì sao đọc bài NÀY thay vì các bài đã research ở Bước 1 (không chỉ nói "chất lượng hơn"); tiêu đề chứa từ khoá mục tiêu ĐẶT GẦN ĐẦU tiêu đề và phản ánh đúng USP.
3. **Viết luận điểm chính (thesis)** — 1-2 câu tóm gọn thông điệp/lập luận chính của TOÀN BÀI, đọc xong biết ngay bài này giúp độc giả điều gì; đặt từ khoá mục tiêu tự nhiên trong 100-150 từ đầu bài (ngay sau H1); mọi H2 phía dưới phải phục vụ đúng luận điểm này.
4. **Dựng cấu trúc H2** — chọn 1 kiểu trình tự phù hợp chủ đề (từng bước theo thời gian / giải quyết vấn đề / tổng quát → cụ thể) rồi giữ nhất quán; mỗi heading trả lời 1 câu hỏi thật; mỗi H2 nên MỞ ĐẦU bằng 1 câu trả lời TRỰC TIẾP câu hỏi của chính heading đó rồi mới giải thích thêm (answer-first — dễ được trích dẫn bởi featured snippet/AI answer engine); mỗi H2 nên có 2-3 điểm hỗ trợ; các H2 cùng cấp dùng CÙNG dạng ngữ pháp (VD tất cả là câu hỏi, hoặc tất cả bắt đầu bằng động từ) để dễ theo dõi.
5. **Khối FAQ ngắn** (3 câu, ưu tiên câu hỏi People Also Ask/tìm kiếm liên quan THẬT quan sát được ở Bước 1, không tự bịa câu hỏi chung chung) + Meta Title (≤60 ký tự, từ khoá mục tiêu GẦN ĐẦU)/Meta Description (140-160 ký tự, từ khoá mục tiêu GẦN ĐẦU, câu chủ động + 1 lời mời hành động ngắn để tăng tỷ lệ click); gợi ý loại Schema markup phù hợp (Article mặc định, thêm FAQPage vì có khối FAQ này — chỉ GỢI Ý loại schema, không cần viết JSON-LD).
6. **CTA ngắn** — 1 câu gợi ý bước tiếp theo phù hợp mục tiêu bài viết/ý định tìm kiếm; nếu ý định là thông tin (informational), ưu tiên gợi ý đọc thêm/hướng dẫn tiếp theo, KHÔNG chèn CTA bán hàng.
7. **Trả lời trực tiếp** theo đúng định dạng output bên dưới — không cần giải thích thêm quy trình bạn đã làm.

**Lưu ý EEAT:** nếu chủ đề cần độ chính xác cao (y tế/pháp lý/tài chính/an toàn trẻ em...), đánh dấu rõ phần nào nên có chuyên gia/nguồn uy tín rà soát trước khi publish. **Độ tin cậy dữ liệu:** ưu tiên dữ liệu/nguồn trong khoảng 12 tháng gần nhất khi có thể, tránh số liệu cũ không còn đúng. **Không bịa số liệu:** nếu không chắc 1 số liệu/thống kê cụ thể, ghi rõ "[cần biên tập viên xác minh]" thay vì tạo ra số liệu không kiểm chứng được. **Mật độ từ khoá:** không có ngưỡng % bắt buộc — dùng từ khoá mục tiêu/phụ tự nhiên xuyên suốt bài, tránh nhồi từ khoá (keyword stuffing); ưu tiên bao quát chủ đề đầy đủ hơn là lặp từ khoá.

## Định dạng output

Trả lời ĐÚNG 1 khối Markdown theo cấu trúc sau, không thêm lời dẫn/giải thích trước hoặc sau khối này:

```
# [Chủ đề]
## Phương án tiêu đề
## USP
## Ý định tìm kiếm
## Luận điểm chính
## Meta
## Dàn ý
## FAQ
## CTA
```
MARKDOWN;
    }

    private function buildBottomStandard(): string
    {
        return <<<'MARKDOWN'
## Quy trình thực hiện

1. **Research thật** — tự tìm/duyệt (qua web search nếu công cụ hỗ trợ, hoặc tri thức đã có + nguồn tham khảo ở trên) 5-10 trang đang xếp hạng tốt cho từ khoá mục tiêu; xác định nội dung hiện có đang thiếu/lỗi thời gì, lời khuyên nào bị lặp lại ở mọi bài mà không có ví dụ thực tế; ngoài SERP truyền thống, cũng lưu ý cách các AI answer engine (Google AI Overview, ChatGPT, Gemini...) hiện đang trả lời câu hỏi này và outline này có thể lấp khoảng trống nào để dễ được các answer engine đó trích dẫn; nếu ước tính được, ghi chú khối lượng tìm kiếm hàng tháng + độ khó từ khoá, cùng số H2/H3 và độ dài trung bình của các trang đang xếp hạng top (dùng làm mốc cho Bước 5/Bước 7).
2. **Xác nhận ý định tìm kiếm** — nêu rõ đây là bài học cái mới / so sánh lựa chọn / giải quyết vấn đề cụ thể / hướng dẫn từng bước; nếu đã cho ở trên, xác nhận hoặc điều chỉnh có giải thích; viết thêm 1 đoạn ngắn trả lời: (a) đọc xong bài, độc giả cần LÀM/ĐẠT được gì, (b) độc giả biết gì lúc BẮT ĐẦU đọc và cần biết gì lúc KẾT THÚC, (c) điều gì khiến họ bỏ bài này đi tìm bài khác — dùng đoạn này để soi lại từng H2 phía dưới có thực sự phục vụ ý định hay không.
3. **Viết luận điểm chính (thesis)** — 1-2 câu tóm gọn thông điệp/lập luận chính của TOÀN BÀI, đọc xong biết ngay bài này giúp độc giả điều gì; đặt từ khoá mục tiêu tự nhiên trong 100-150 từ đầu bài (ngay sau H1); mọi H2 phía dưới phải phục vụ đúng luận điểm này, không lạc đề.
4. **Xác định USP** — 1-2 câu CỤ THỂ vì sao đọc bài NÀY thay vì các bài đã research ở Bước 1 (VD nguồn/dữ liệu mới hơn, góc nhìn khác, hướng dẫn chi tiết hơn) — không chỉ nói chung "chất lượng hơn".
5. **Đề xuất 2-3 phương án tiêu đề (H1)** — chứa từ khoá mục tiêu ĐẶT GẦN ĐẦU tiêu đề, chạm đúng pain point/mục tiêu bài viết, phản ánh USP ở Bước 4.
6. **Chọn kiểu bài (structure archetype)** — dựa vào ý định tìm kiếm ở Bước 2 + cách các trang top đang trình bày ở Bước 1, chọn 1 trong 4 kiểu: **Hướng dẫn tuần tự** (các bước có thứ tự, phụ thuộc lẫn nhau — cho từ khoá quy trình/"cách"), **Framework/hệ thống** (mô hình có tên với các thành phần xác định — cho từ khoá "hệ thống"/"phương pháp"), **So sánh/kết luận** (đối chiếu song song các lựa chọn kèm khuyến nghị rõ ràng — cho từ khoá "vs"/"nên chọn"), **Danh sách tài nguyên** (liệt kê công cụ/ví dụ đã chọn lọc — cho từ khoá "tốt nhất"); nêu rõ kiểu đã chọn + lý do trong 1 câu, và 1 câu nói rõ kiểu NÊN TRÁNH vì sao — dùng kiểu này định hướng cấu trúc H2/H3 ở Bước 7.
7. **Dựng cấu trúc H2/H3** — phù hợp với kiểu bài đã chọn ở Bước 6; chọn 1 kiểu trình tự phù hợp chủ đề (từng bước theo thời gian / giải quyết vấn đề / nguyên nhân-kết quả / tổng quát → cụ thể) rồi giữ nhất quán; MỖI heading trả lời 1 câu hỏi thật của độc giả; MỖI H2/H3 nên MỞ ĐẦU bằng 1-2 câu trả lời TRỰC TIẾP câu hỏi của chính heading đó rồi mới giải thích/mở rộng thêm (answer-first — dễ được trích dẫn bởi featured snippet/AI answer engine); câu hỏi/ý chính của toàn bài phải được trả lời SỚM (không chôn ở cuối bài); mỗi H2 nên có 3-5 điểm/H3 hỗ trợ (không quá ít khiến thiếu chiều sâu, không quá nhiều khiến dàn ý loãng) — số H2/H3 nên bằng hoặc nhiều hơn mốc đã ghi ở Bước 1 nếu bạn có đủ chất liệu để mở rộng có ý nghĩa; H2 dự kiến dài hơn 400 từ nên có ít nhất 2 H3; mỗi H3 phải nêu 1 điểm CỤ THỂ, đọc riêng tiêu đề H3 cũng hiểu được nội dung bên trong (KHÔNG đặt H3 kiểu nhãn chung như "Ví dụ"/"Lưu ý"/"Mẹo" — viết lại thành câu nêu rõ điểm đó); các heading CÙNG CẤP dùng CÙNG dạng ngữ pháp (VD tất cả là câu hỏi, hoặc tất cả bắt đầu bằng động từ) để dễ theo dõi.
8. **Làm rõ nội dung mỗi heading** — điểm chính (bullet), loại bằng chứng/ví dụ/số liệu nên đưa vào, vị trí nên có hình ảnh/bảng (nếu gợi ý hình ảnh, kèm luôn 1 alt text ngắn mô tả đúng nội dung hình, có thể tự nhiên chứa từ khoá liên quan — KHÔNG nhồi từ khoá vào alt text); nếu 1 phần dùng danh sách/bullet, PHẢI có 1 câu dẫn nhập nêu ngữ cảnh trước danh sách đó, không thả bullet trơ trọi ngay sau heading; với MỖI H2, thêm 1 câu ghi rõ phần này làm KHÁC gì so với các bài đối thủ điển hình về chủ đề này (không chỉ nói chung "chi tiết hơn" — nêu cụ thể góc nhìn/dữ liệu/ví dụ khác biệt).
9. **Khối FAQ** — 4-6 câu hỏi dạng "People Also Ask", ưu tiên chuyển hoá từ các nghi ngờ/lý do chưa tin đã nêu ở trên (nếu có); ưu tiên câu hỏi PAA/tìm kiếm liên quan THẬT quan sát được khi research ở Bước 1, không tự bịa câu hỏi chung chung nếu không quan sát được.
10. **Meta Title (≤60 ký tự, từ khoá mục tiêu GẦN ĐẦU) + Meta Description (140-160 ký tự, từ khoá mục tiêu GẦN ĐẦU, câu chủ động + 1 lời mời hành động ngắn để tăng tỷ lệ click)** — gợi ý loại Schema markup phù hợp — Article/BlogPosting mặc định, thêm FAQPage nếu dùng khối FAQ ở Bước 9, thêm HowTo nếu kiểu bài đã chọn ở Bước 6 là Hướng dẫn tuần tự, thêm ItemList nếu là Danh sách tài nguyên (chỉ GỢI Ý loại schema, không cần viết JSON-LD).
11. **Gợi ý internal link + external link** — GỢI Ý LOẠI nguồn/chủ đề nên link tới, KHÔNG bịa URL cụ thể (bạn không biết URL nào tồn tại thật trên platform này); với MỖI gợi ý internal link, kèm 1 đề xuất anchor text ngắn (2-5 từ, tự nhiên trong câu, không nhồi từ khoá) để biên tập viên dùng khi chèn link.{{ROLE_LINK_NOTE}}
12. **Gợi ý CTA/bước tiếp theo** — 1 CTA cụ thể phù hợp mục tiêu bài viết/ý định tìm kiếm đã cho ở trên (VD đọc thêm bài liên quan, dùng công cụ/máy tính, đăng ký nhận tư vấn, liên hệ...); nếu ý định tìm kiếm là thông tin (informational), ưu tiên CTA hướng dẫn tiếp theo, KHÔNG chèn CTA bán hàng cứng nhắc.
13. **Tự rà lại (self-check)** — từ khoá mục tiêu có ở tiêu đề VÀ ít nhất 1 H2 không; đã bao quát các chủ đề phụ chính thấy ở Bước 1 chưa; outline có trả lời đúng ý định tìm kiếm không; có phần nào trùng lặp/dư không; luồng có theo ĐÚNG 1 trình tự đã chọn ở Bước 7 không, và có nhất quán với kiểu bài đã chọn ở Bước 6 không; có ít nhất 1 phần "information gain" (thứ độc giả không dễ tìm thấy ở các bài đã research Bước 1) chưa — nếu chưa, bổ sung trước khi trả lời.

**Lưu ý EEAT:** nếu chủ đề cần độ chính xác cao (y tế/pháp lý/tài chính/an toàn trẻ em...), đánh dấu rõ phần nào nên có chuyên gia/nguồn uy tín rà soát trước khi publish. **Độ tin cậy dữ liệu:** ưu tiên dữ liệu/nguồn trong khoảng 12 tháng gần nhất khi có thể; với số liệu/khẳng định quan trọng, gợi ý trích dẫn 2-3 nguồn uy tín khác nhau, không chỉ dựa vào 1 nguồn. **Không bịa số liệu:** nếu không chắc 1 số liệu/thống kê cụ thể, ghi rõ "[cần biên tập viên xác minh]" thay vì tạo ra số liệu không kiểm chứng được. **Mật độ từ khoá:** không có ngưỡng % bắt buộc — dùng từ khoá mục tiêu/phụ tự nhiên xuyên suốt bài, tránh nhồi từ khoá (keyword stuffing); ưu tiên bao quát chủ đề đầy đủ hơn là lặp từ khoá.

## Định dạng output

Trả lời ĐÚNG 1 khối Markdown theo cấu trúc sau, không thêm lời dẫn/giải thích trước hoặc sau khối này:

```
# [Chủ đề]
## Phương án tiêu đề
## USP
## Ý định tìm kiếm
## Kiểu bài
## Luận điểm chính
## Meta
## Dàn ý
## FAQ
## Gợi ý Internal/External Link
## CTA
```
MARKDOWN;
    }

    /** §4.1 (v1.1) — mở rộng: thêm đánh giá độ khó cạnh tranh, ví dụ/số liệu cụ thể mỗi bullet, FAQ 6-8 câu. */
    private function buildBottomDetailed(): string
    {
        return <<<'MARKDOWN'
## Quy trình thực hiện (mở rộng)

1. **Research thật, sâu** — tự tìm/duyệt (qua web search nếu công cụ hỗ trợ, hoặc tri thức đã có + nguồn tham khảo ở trên) 8-15 trang đang xếp hạng tốt cho từ khoá mục tiêu; xác định nội dung hiện có đang thiếu/lỗi thời gì, lời khuyên nào bị lặp lại mà không có ví dụ thực tế; ngoài SERP truyền thống, cũng lưu ý cách các AI answer engine (Google AI Overview, ChatGPT, Gemini...) hiện đang trả lời câu hỏi này và outline này có thể lấp khoảng trống nào để dễ được các answer engine đó trích dẫn; nếu ước tính được, ghi chú khối lượng tìm kiếm hàng tháng + độ khó từ khoá.
2. **Đánh giá độ khó cạnh tranh** — nhận xét ngắn về mức độ đầu tư nội dung của các trang đang xếp hạng top (độ dài, số H2/H3, độ chuyên sâu, có backlink/thương hiệu mạnh không) để biên tập viên biết cần đầu tư tới đâu — dùng làm mốc benchmark cho Bước 8/9.
3. **Xác nhận ý định tìm kiếm** — nêu rõ đây là bài học cái mới / so sánh lựa chọn / giải quyết vấn đề cụ thể / hướng dẫn từng bước; nếu đã cho ở trên, xác nhận hoặc điều chỉnh có giải thích; viết thêm 1 đoạn ngắn trả lời: (a) đọc xong bài, độc giả cần LÀM/ĐẠT được gì, (b) độc giả biết gì lúc BẮT ĐẦU đọc và cần biết gì lúc KẾT THÚC, (c) điều gì khiến họ bỏ bài này đi tìm bài khác — dùng đoạn này để soi lại từng H2 phía dưới có thực sự phục vụ ý định hay không.
4. **Viết luận điểm chính (thesis)** — 1-2 câu tóm gọn thông điệp/lập luận chính của TOÀN BÀI, đọc xong biết ngay bài này giúp độc giả điều gì; đặt từ khoá mục tiêu tự nhiên trong 100-150 từ đầu bài (ngay sau H1); mọi H2 phía dưới phải phục vụ đúng luận điểm này, không lạc đề.
5. **Xác định USP** — 1-2 câu CỤ THỂ vì sao đọc bài NÀY thay vì các bài đã research/đánh giá ở Bước 1-2 (VD nguồn/dữ liệu mới hơn, góc nhìn khác, hướng dẫn chi tiết hơn) — không chỉ nói chung "chất lượng hơn".
6. **Đề xuất 2-3 phương án tiêu đề (H1)** — chứa từ khoá mục tiêu ĐẶT GẦN ĐẦU tiêu đề, chạm đúng pain point/mục tiêu bài viết, phản ánh USP ở Bước 5.
7. **Chọn kiểu bài (structure archetype)** — dựa vào ý định tìm kiếm ở Bước 3 + cách các trang top đang trình bày (Bước 1) + mức đầu tư của đối thủ (Bước 2), chọn 1 trong 4 kiểu: **Hướng dẫn tuần tự** (các bước có thứ tự, phụ thuộc lẫn nhau — cho từ khoá quy trình/"cách"), **Framework/hệ thống** (mô hình có tên với các thành phần xác định — cho từ khoá "hệ thống"/"phương pháp"), **So sánh/kết luận** (đối chiếu song song các lựa chọn kèm khuyến nghị rõ ràng — cho từ khoá "vs"/"nên chọn"), **Danh sách tài nguyên** (liệt kê công cụ/ví dụ đã chọn lọc — cho từ khoá "tốt nhất"); nêu rõ kiểu đã chọn + lý do trong 1 câu, và 1 câu nói rõ kiểu NÊN TRÁNH vì sao — dùng kiểu này định hướng cấu trúc H2/H3 ở Bước 8.
8. **Dựng cấu trúc H2/H3** — phù hợp với kiểu bài đã chọn ở Bước 7; chọn 1 kiểu trình tự phù hợp chủ đề (từng bước theo thời gian / giải quyết vấn đề / nguyên nhân-kết quả / tổng quát → cụ thể) rồi giữ nhất quán; MỖI heading trả lời 1 câu hỏi thật của độc giả; MỖI H2/H3 nên MỞ ĐẦU bằng 1-2 câu trả lời TRỰC TIẾP câu hỏi của chính heading đó rồi mới giải thích/mở rộng thêm (answer-first — dễ được trích dẫn bởi featured snippet/AI answer engine); câu hỏi/ý chính của toàn bài phải được trả lời SỚM (không chôn ở cuối bài); mỗi H2 nên có 3-5 điểm/H3 hỗ trợ (không quá ít khiến thiếu chiều sâu, không quá nhiều khiến dàn ý loãng) — số H2/H3 nên BẰNG hoặc NHIỀU HƠN mốc đã đánh giá ở Bước 2; H2 dự kiến dài hơn 400 từ nên có ít nhất 2 H3; mỗi H3 phải nêu 1 điểm CỤ THỂ, đọc riêng tiêu đề H3 cũng hiểu được nội dung bên trong (KHÔNG đặt H3 kiểu nhãn chung như "Ví dụ"/"Lưu ý"/"Mẹo" — viết lại thành câu nêu rõ điểm đó); các heading CÙNG CẤP dùng CÙNG dạng ngữ pháp (VD tất cả là câu hỏi, hoặc tất cả bắt đầu bằng động từ) để dễ theo dõi.
9. **Làm rõ nội dung mỗi heading, có chiều sâu** — điểm chính (bullet), với MỖI bullet đưa kèm 1 ví dụ/số liệu/dẫn chứng CỤ THỂ (không chỉ gợi ý "nên có ví dụ" mà đề xuất luôn nội dung ví dụ đó nếu bạn biết), vị trí nên có hình ảnh/bảng (nếu gợi ý hình ảnh, kèm luôn 1 alt text ngắn mô tả đúng nội dung hình, có thể tự nhiên chứa từ khoá liên quan — KHÔNG nhồi từ khoá vào alt text); nếu 1 phần dùng danh sách/bullet, PHẢI có 1 câu dẫn nhập nêu ngữ cảnh trước danh sách đó, không thả bullet trơ trọi ngay sau heading; với MỖI H2, thêm 1 câu ghi rõ phần này làm KHÁC gì so với các bài đối thủ điển hình về chủ đề này (không chỉ nói chung "chi tiết hơn" — nêu cụ thể góc nhìn/dữ liệu/ví dụ khác biệt).
10. **Khối FAQ mở rộng** — 6-8 câu hỏi dạng "People Also Ask", ưu tiên chuyển hoá từ các nghi ngờ/lý do chưa tin đã nêu ở trên (nếu có); ưu tiên câu hỏi PAA/tìm kiếm liên quan THẬT quan sát được khi research ở Bước 1, không tự bịa câu hỏi chung chung nếu không quan sát được.
11. **Meta Title (≤60 ký tự, từ khoá mục tiêu GẦN ĐẦU) + Meta Description (140-160 ký tự, từ khoá mục tiêu GẦN ĐẦU, câu chủ động + 1 lời mời hành động ngắn để tăng tỷ lệ click)** — gợi ý loại Schema markup phù hợp — Article/BlogPosting mặc định, thêm FAQPage nếu dùng khối FAQ ở Bước 10, thêm HowTo nếu kiểu bài đã chọn ở Bước 7 là Hướng dẫn tuần tự, thêm ItemList nếu là Danh sách tài nguyên (chỉ GỢI Ý loại schema, không cần viết JSON-LD).
12. **Gợi ý internal link + external link** — GỢI Ý LOẠI nguồn/chủ đề nên link tới, KHÔNG bịa URL cụ thể (bạn không biết URL nào tồn tại thật trên platform này); với MỖI gợi ý internal link, kèm 1 đề xuất anchor text ngắn (2-5 từ, tự nhiên trong câu, không nhồi từ khoá) để biên tập viên dùng khi chèn link.{{ROLE_LINK_NOTE}}
13. **Ước lượng số từ mỗi phần** — nếu có số từ mong muốn ở trên, chia ước lượng số từ cho từng H2 (kể cả phần mở đầu) sao cho tổng khớp mục tiêu, cho phép sai số ±10%.
14. **Gợi ý CTA/bước tiếp theo** — 1 CTA cụ thể phù hợp mục tiêu bài viết/ý định tìm kiếm đã cho ở trên (VD đọc thêm bài liên quan, dùng công cụ/máy tính, đăng ký nhận tư vấn, liên hệ...); nếu ý định tìm kiếm là thông tin (informational), ưu tiên CTA hướng dẫn tiếp theo, KHÔNG chèn CTA bán hàng cứng nhắc.
15. **Tự rà lại (self-check)** — từ khoá mục tiêu có ở tiêu đề VÀ ít nhất 1 H2 không; đã bao quát các chủ đề phụ chính thấy ở Bước 1 chưa; outline có trả lời đúng ý định tìm kiếm không; có phần nào trùng lặp/dư không; luồng có theo ĐÚNG 1 trình tự đã chọn ở Bước 8 không, và có nhất quán với kiểu bài đã chọn ở Bước 7 không; có ít nhất 1 phần "information gain" (thứ độc giả không dễ tìm thấy ở các bài đã research Bước 1) chưa — nếu chưa, bổ sung trước khi trả lời.

**Lưu ý EEAT:** nếu chủ đề cần độ chính xác cao (y tế/pháp lý/tài chính/an toàn trẻ em...), đánh dấu rõ phần nào nên có chuyên gia/nguồn uy tín rà soát trước khi publish, và gợi ý cụ thể loại chuyên gia/nguồn nên tham khảo. **Độ tin cậy dữ liệu:** ưu tiên dữ liệu/nguồn trong khoảng 12 tháng gần nhất khi có thể; với số liệu/khẳng định quan trọng, gợi ý trích dẫn 2-3 nguồn uy tín khác nhau, không chỉ dựa vào 1 nguồn. **Không bịa số liệu:** nếu không chắc 1 số liệu/thống kê cụ thể, ghi rõ "[cần biên tập viên xác minh]" thay vì tạo ra số liệu không kiểm chứng được. **Mật độ từ khoá:** không có ngưỡng % bắt buộc — dùng từ khoá mục tiêu/phụ tự nhiên xuyên suốt bài, tránh nhồi từ khoá (keyword stuffing); ưu tiên bao quát chủ đề đầy đủ hơn là lặp từ khoá.

## Định dạng output

Trả lời ĐÚNG 1 khối Markdown theo cấu trúc sau, không thêm lời dẫn/giải thích trước hoặc sau khối này:

```
# [Chủ đề]
## Phương án tiêu đề
## USP
## Đánh giá độ khó cạnh tranh
## Ý định tìm kiếm
## Kiểu bài
## Luận điểm chính
## Meta
## Dàn ý
## FAQ
## Gợi ý Internal/External Link
## CTA
```
MARKDOWN;
    }
}
