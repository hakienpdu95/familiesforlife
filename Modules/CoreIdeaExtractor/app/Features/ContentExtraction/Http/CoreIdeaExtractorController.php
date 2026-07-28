<?php

namespace Modules\CoreIdeaExtractor\Features\ContentExtraction\Http;

use App\Http\Controllers\Controller;
use App\Services\AI\Exceptions\AIProviderConfigException;
use App\Services\AI\Exceptions\UnknownModelPricingException;
use App\Shared\Tenancy\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;
use Modules\CoreIdeaExtractor\Enums\ExtractionConfidence;
use Modules\CoreIdeaExtractor\Features\CategoryFoundation\Actions\ListCategoryFoundationsAction;
use Modules\CoreIdeaExtractor\Features\ContentExtraction\Actions\ComputeExtractionConfidenceAction;
use Modules\CoreIdeaExtractor\Features\ContentExtraction\Actions\ExtractRawContentAction;
use Modules\CoreIdeaExtractor\Features\ContentExtraction\Actions\FetchArticleHtmlAction;
use Modules\CoreIdeaExtractor\Features\ContentExtraction\Actions\FetchArticlesBatchAction;
use Modules\CoreIdeaExtractor\Features\ContentExtraction\Actions\RunLayer2ExtractionAction;
use Modules\CoreIdeaExtractor\Features\ContentExtraction\Data\BatchSourceResultData;
use Modules\CoreIdeaExtractor\Features\ContentExtraction\Data\ExtractBatchRequestData;
use Modules\CoreIdeaExtractor\Features\ContentExtraction\Data\ExtractBatchResultData;
use Modules\CoreIdeaExtractor\Features\ContentExtraction\Data\ExtractRequestData;
use Modules\CoreIdeaExtractor\Features\ContentExtraction\Data\HeadingData;
use Modules\CoreIdeaExtractor\Features\ContentExtraction\Data\RawExtractionData;
use Modules\CoreIdeaExtractor\Features\ContentExtraction\Data\SourceStructureData;
use Modules\CoreIdeaExtractor\Features\ContentExtraction\Exceptions\AiBudgetExceededException;
use Modules\CoreIdeaExtractor\Features\ContentExtraction\Exceptions\UrlFetchException;

class CoreIdeaExtractorController extends Controller
{
    /**
     * spec/CoreIdeaExtractor.md §12 (v1.4) — nạp sẵn danh sách chuyên mục + Category Content
     * Foundation qua Js::from() trong view, KHÔNG qua AJAX riêng — danh sách nhỏ (toàn bộ cây
     * chuyên mục active), tránh round-trip thừa cho picker xuất hiện ngay khi tải trang.
     */
    public function index(ListCategoryFoundationsAction $listCategoryFoundations): View
    {
        return view('coreideaextractor::index', [
            'categoryFoundations' => $listCategoryFoundations->handle(),
        ]);
    }

    /**
     * Chạy đồng bộ trong request (KHÔNG queue) — Layer 1 chỉ fetch+parse HTML, không có lệnh
     * gọi AI 5-30s nào cần chạy nền (khác Aicem). Lỗi fetch (mạng/SSRF/HTTP status/content-type)
     * KHÔNG phải lỗi validate — vẫn trả 200 với extraction_confidence=low + notes rõ nguyên
     * nhân, đúng tinh thần "luôn trả kết quả có cấu trúc" của spec §1.
     *
     * `html` là lối thoát khi trang chặn crawl tự động (403/bot protection, xem
     * FetchArticleHtmlAction) — người dùng tự mở trang bằng trình duyệt thật, dán mã nguồn
     * (View Source) hoặc chỉ 1 đoạn fragment (VD nội dung trong `<div class="post__content">`)
     * vào form, bỏ qua hẳn bước fetch (không có lỗi mạng/SSRF/WAF nào có thể xảy ra ở nhánh này).
     */
    public function extract(Request $request, FetchArticleHtmlAction $fetch, ExtractRawContentAction $extractRaw, ComputeExtractionConfidenceAction $computeConfidence): JsonResponse
    {
        $data = ExtractRequestData::from($request->validate([
            'url'                    => ['nullable', 'url', 'max:2048', 'required_without:html'],
            'html'                   => ['nullable', 'string', 'max:'.config('core_idea_extractor.paste.max_chars', 2_000_000), 'required_without:url'],
            'main_content_selector'  => ['nullable', 'string', 'max:255'],
            'force_refresh'          => ['nullable', 'boolean'],
            'source_language'        => ['nullable', 'string', 'in:vi,en,th,id'],
        ]));

        $pasted = $data->html !== null && trim($data->html) !== '';

        if ($pasted) {
            $html = $data->html;
        } else {
            try {
                $html = $fetch->handle($data->url, $data->force_refresh);
            } catch (UrlFetchException $e) {
                return response()->json($this->buildResult(
                    title: null,
                    metaDescription: null,
                    keywords: [],
                    headings: [],
                    mainContent: '',
                    publishDate: null,
                    author: null,
                    language: 'unknown',
                    confidence: ExtractionConfidence::Low,
                    notes: $e->getMessage(),
                )->toApiArray());
            }
        }

        $rawHtmlChars = mb_strlen($html);

        $extracted        = $extractRaw->handle($html, $data->main_content_selector, $data->source_language);
        $confidenceResult = $computeConfidence->handle($extracted);
        $notes            = $this->appendSelectorNote($confidenceResult['notes'], $data->main_content_selector, $extracted['custom_selector_matched']);
        $notes            = $this->appendPastedFragmentNote($notes, $pasted, $extracted['title']);
        $notes            = $this->appendStructureNote($notes, $extracted['source_structure']);
        $notes            = $this->appendLanguageMismatchNote($notes, $extracted['language_mismatch_suspected'], $extracted['language']);

        // sections[] phải tính LẠI trên main_content ĐÃ CẮT (không dùng thẳng $extracted['sections']
        // — vốn tính trên bản CHƯA cắt trong ExtractRawContentAction::handle()) — nếu không, khi
        // main_content vượt ngưỡng cắt (hiếm với single-URL vì ngưỡng tới 100.000 ký tự, nhưng vẫn
        // có thể xảy ra), sections sẽ "rò rỉ" phần nội dung đã bị cắt bỏ ra ngoài, không nhất quán
        // với main_content THẬT SỰ trả về.
        $finalMainContent = $this->truncateMainContent($extracted['main_content']);
        $contentReduction = $this->computeContentReduction($rawHtmlChars, $finalMainContent);

        $result = $this->buildResult(
            title: $extracted['title'],
            metaDescription: $extracted['meta_description'],
            keywords: $extracted['keywords'],
            headings: $extracted['headings'],
            sections: $extractRaw->buildSections($finalMainContent, $extracted['headings']),
            mainContent: $finalMainContent,
            publishDate: $extracted['publish_date'],
            author: $extracted['author'],
            language: $extracted['language'],
            confidence: $confidenceResult['confidence'],
            notes: $notes,
            wordCount: $extracted['word_count'],
            headingCount: $extracted['meaningful_heading_count'],
            sourceStructure: $extracted['source_structure'],
            canonicalUrl: $extracted['canonical_url'],
            contentCategory: $extracted['content_category'],
            declaredContentType: $extracted['declared_content_type'],
            dateModified: $extracted['date_modified'],
            publisherName: $extracted['publisher_name'],
            contentTypeSignal: $extracted['content_type_signal'],
            rawHtmlChars: $contentReduction['raw_html_chars'],
            mainContentChars: $contentReduction['main_content_chars'],
            reductionPercent: $contentReduction['reduction_percent'],
        );

        return response()->json($result->toApiArray());
    }

    /**
     * Batch tối đa `core_idea_extractor.batch.max_urls` URL, fetch song song qua
     * FetchArticlesBatchAction (Http::pool) — 1 nguồn lỗi/bị chặn (Cloudflare/WAF) KHÔNG làm
     * hỏng cả batch, chỉ xuất status='blocked'/'error' cho riêng nguồn đó (xem
     * BatchSourceResultData). main_content mỗi nguồn cắt ngắn hơn mode 1-URL (xem
     * truncateBatchMainContent()) vì kết quả dùng để copy nguyên JSON dán vào chat AI —
     * 7 nguồn full 100000 ký tự/nguồn sẽ quá lớn để paste.
     */
    public function extractBatch(Request $request, FetchArticlesBatchAction $fetchBatch, ExtractRawContentAction $extractRaw, ComputeExtractionConfidenceAction $computeConfidence): JsonResponse
    {
        $maxUrls = (int) config('core_idea_extractor.batch.max_urls', 7);

        $data = ExtractBatchRequestData::from($request->validate([
            'urls'                   => ['required', 'array', 'min:1', "max:{$maxUrls}"],
            'urls.*'                 => ['url', 'max:2048', 'distinct'],
            'topic'                  => ['nullable', 'string', 'max:255'],
            'audience'               => ['nullable', 'string', 'max:500'],
            'goal'                   => ['nullable', 'string', 'max:500'],
            'constraints'            => ['nullable', 'string', 'max:500'],
            'style_sample'           => ['nullable', 'string', 'max:3000'],
            'main_content_selector'  => ['nullable', 'string', 'max:255'],
            'main_content_selectors'   => ['nullable', 'array'],
            'main_content_selectors.*' => ['nullable', 'string', 'max:255'],
            'force_refresh'          => ['nullable', 'boolean'],
            'source_language'        => ['nullable', 'string', 'in:vi,en,th,id'],
        ]));

        $fetched = $fetchBatch->handle($data->urls, $data->force_refresh);

        $sources = [];
        $success = 0;
        $blocked = 0;
        $error   = 0;

        foreach ($data->urls as $key => $url) {
            $item   = $fetched[$key];
            $domain = $this->resolveDomain($url);

            if ($item['failure'] !== null) {
                $sources[] = BatchSourceResultData::failure(
                    url: $url,
                    finalUrl: $item['resolved_url'],
                    domain: $domain,
                    status: $item['failure']['status'],
                    failureType: $item['failure']['failure_type'],
                    httpStatus: $item['failure']['http_status'],
                    errorMessage: $item['failure']['error_message'],
                    fetchedAt: $item['fetched_at'],
                );

                $item['failure']['status'] === 'blocked' ? $blocked++ : $error++;

                continue;
            }

            $selector         = $this->resolveSelectorForUrl($data, $key);
            $rawHtmlChars     = mb_strlen($item['html']);
            $extracted        = $extractRaw->handle($item['html'], $selector, $data->source_language);
            $confidenceResult = $computeConfidence->handle($extracted);
            $notes            = $this->appendSelectorNote($confidenceResult['notes'], $selector, $extracted['custom_selector_matched']);
            $notes            = $this->appendStructureNote($notes, $extracted['source_structure']);
            $notes            = $this->appendLanguageMismatchNote($notes, $extracted['language_mismatch_suspected'], $extracted['language']);
            $selection        = $this->truncateBatchMainContent($extracted['main_content'], $data->topic);
            $mainContent      = $selection['text'];
            $notes            = $this->appendRelevanceNote($notes, $selection['relevance_applied'], $data->topic);
            $contentHash      = $this->computeContentHash($mainContent);
            $contentReduction = $this->computeContentReduction($rawHtmlChars, $mainContent);

            $sources[] = BatchSourceResultData::success(
                url: $url,
                finalUrl: $item['resolved_url'],
                domain: $domain,
                httpStatus: $item['http_status'],
                extraction: $this->buildResult(
                    title: $extracted['title'],
                    metaDescription: $extracted['meta_description'],
                    keywords: $extracted['keywords'],
                    headings: $extracted['headings'],
                    // Tính LẠI trên main_content ĐÃ CẮT theo ngân sách batch (không dùng thẳng
                    // $extracted['sections'] — tính trên bản CHƯA cắt) — nếu không, sections sẽ
                    // rò rỉ nội dung đã bị cắt bỏ, làm mất tác dụng giới hạn ngân sách ký tự/nguồn
                    // (xem docblock ExtractRawContentAction::buildSections()).
                    sections: $extractRaw->buildSections($mainContent, $extracted['headings']),
                    mainContent: $mainContent,
                    publishDate: $extracted['publish_date'],
                    author: $extracted['author'],
                    language: $extracted['language'],
                    confidence: $confidenceResult['confidence'],
                    notes: $notes,
                    wordCount: $extracted['word_count'],
                    headingCount: $extracted['meaningful_heading_count'],
                    sourceStructure: $extracted['source_structure'],
                    canonicalUrl: $extracted['canonical_url'],
                    contentCategory: $extracted['content_category'],
                    declaredContentType: $extracted['declared_content_type'],
                    dateModified: $extracted['date_modified'],
                    publisherName: $extracted['publisher_name'],
                    contentTypeSignal: $extracted['content_type_signal'],
                    rawHtmlChars: $contentReduction['raw_html_chars'],
                    mainContentChars: $contentReduction['main_content_chars'],
                    reductionPercent: $contentReduction['reduction_percent'],
                ),
                contentHash: $contentHash,
                duplicateOf: $this->resolveDuplicateOf($contentHash, $url),
                fetchedAt: $item['fetched_at'],
            );

            $success++;
        }

        $result = new ExtractBatchResultData(
            topic: $data->topic,
            audience: $data->audience,
            goal: $data->goal,
            constraints: $data->constraints,
            style_sample: $data->style_sample,
            processed_at: now()->toIso8601String(),
            requested_count: count($data->urls),
            success_count: $success,
            blocked_count: $blocked,
            error_count: $error,
            sources: $sources,
        );

        return response()->json($result->toApiArray());
    }

    /**
     * Tự động hoá "Layer 2" — CHỈ chạy khi người dùng bấm nút thủ công (nút "Chạy Layer 2 bằng
     * AI" ở index.blade.php), KHÔNG tự động sau extract()/extractBatch() (yêu cầu 2026-07-28:
     * kiểm soát chi phí + cho phép tối ưu Layer 1/ngữ cảnh trước khi tốn tiền gọi AI thật).
     * `prompt` nhận NGUYÊN VĂN chuỗi đã build sẵn ở client (copyPromptForAi()/buildLayer2Prompt())
     * — xem docblock RunLayer2ExtractionAction để biết lý do không build lại ở PHP.
     */
    public function runLayer2(Request $request, RunLayer2ExtractionAction $action): JsonResponse
    {
        $data = $request->validate([
            'prompt' => ['required', 'string', 'max:'.config('core_idea_extractor.layer2.max_prompt_chars', 300000)],
        ]);

        $organization = TenantContext::get();

        if (! $organization) {
            return response()->json(['message' => 'Không xác định được tổ chức hiện tại — không thể gọi AI.'], 422);
        }

        try {
            $result = $action->handle($organization, $data['prompt']);
        } catch (AiBudgetExceededException|AIProviderConfigException|UnknownModelPricingException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json($result);
    }

    private function resolveDomain(string $url): string
    {
        return parse_url($url, PHP_URL_HOST) ?: $url;
    }

    /**
     * Selector áp dụng cho `urls[$key]` — ưu tiên override riêng ở `main_content_selectors[$key]`
     * (xem docblock field ở `ExtractBatchRequestData`), rồi mới tới `main_content_selector` chung
     * cho cả batch, cuối cùng `null` (tự động `resolveContentRoot()`). Nhiều nguồn trong 1 batch
     * thường thuộc nhiều domain khác nhau — mỗi domain có bố cục CSS riêng, 1 selector chung hiếm
     * khi đúng cho tất cả.
     */
    private function resolveSelectorForUrl(ExtractBatchRequestData $data, int $key): ?string
    {
        $override = $data->main_content_selectors[$key] ?? null;

        return ($override !== null && trim($override) !== '') ? $override : $data->main_content_selector;
    }

    /** @return array{text: string, relevance_applied: bool} */
    private function truncateBatchMainContent(string $text, ?string $topic): array
    {
        $max = (int) config('core_idea_extractor.batch.max_main_content_chars_per_source', 12000);

        return $this->selectRelevantContent($text, $max, $topic);
    }

    /** Độ dài tối thiểu (ký tự) để 1 từ trong `topic` được coi là từ khoá — loại từ nối/hư từ quá ngắn (VD "và", "là", "vs" tiếng Anh 2 ký tự vẫn qua nhưng "a", "ở" 1 ký tự bị loại). */
    private const MIN_TOPIC_KEYWORD_CHARS = 2;

    /**
     * spec/CoreIdeaExtractor.md — khi main_content 1 nguồn dài hơn ngân sách ký tự cho batch,
     * MẶC ĐỊNH cắt theo thứ tự xuất hiện (truncateAtBoundary) sẽ luôn giữ phần ĐẦU bài và bỏ phần
     * CUỐI — nhưng đoạn nói đúng `topic` người dùng đang nghiên cứu có thể nằm ở giữa/cuối bài
     * (VD phần "lời khuyên chuyên gia" ở cuối 1 bài dài), bị cắt mất dù mới là phần đáng giá nhất
     * để paste vào chat AI. Khi có `topic`, ưu tiên GIỮ các đoạn văn (tách bởi dòng trống — xem
     * BLOCK_TAGS ở ExtractRawContentAction) có khớp từ khoá topic, bất kể vị trí trong bài, thay
     * vì luôn ưu tiên phần đầu.
     *
     * Đây CHỈ là chọn lọc HIỂN THỊ trên main_content đã trích được — không ảnh hưởng gì tới việc
     * fetch/parse HTML hay các field khác (xem docblock $topic ở ExtractBatchRequestData). Đoạn
     * mở đầu (lead) luôn được giữ làm điểm neo ngữ cảnh dù có khớp topic hay không — thiếu nó các
     * đoạn còn lại dễ đọc rời rạc, không rõ bài đang nói về gì. Các đoạn bị lược bỏ ở giữa được
     * đánh dấu bằng "[…]" để AI đọc JSON biết đây là nội dung đã bị cắt có chủ đích, không phải
     * bài viết tự nhiên đứt đoạn (tránh hiểu nhầm nguồn viết lủng củng).
     *
     * Không có `topic`, hoặc bài chỉ có 1 đoạn duy nhất (không tách được), hoặc không đoạn nào
     * khớp từ khoá → rơi về truncateAtBoundary() như cũ (không đổi hành vi mặc định).
     *
     * @return array{text: string, relevance_applied: bool}
     */
    private function selectRelevantContent(string $text, int $max, ?string $topic): array
    {
        if (mb_strlen($text) <= $max) {
            return ['text' => $text, 'relevance_applied' => false];
        }

        $keywords = $this->extractTopicKeywords($topic);

        if ($keywords === []) {
            return ['text' => $this->truncateAtBoundary($text, $max), 'relevance_applied' => false];
        }

        $paragraphs = preg_split('/\n{2,}/', trim($text)) ?: [];

        if (count($paragraphs) <= 1) {
            return ['text' => $this->truncateAtBoundary($text, $max), 'relevance_applied' => false];
        }

        $scored = array_map(
            fn (int $index, string $paragraph) => [
                'index' => $index,
                'len'   => mb_strlen($paragraph),
                'score' => $this->scoreParagraphRelevance($paragraph, $keywords),
            ],
            array_keys($paragraphs),
            $paragraphs,
        );

        $selected = [$scored[0]['index'] => true];
        $budget   = $max - $scored[0]['len'];

        $candidates = array_slice($scored, 1);
        usort($candidates, static fn (array $a, array $b) => $b['score'] <=> $a['score'] ?: $a['index'] <=> $b['index']);

        $addedRelevantParagraph = false;

        foreach ($candidates as $candidate) {
            if ($candidate['score'] <= 0 || $candidate['len'] + 2 > $budget) {
                continue;
            }

            $selected[$candidate['index']] = true;
            $budget -= $candidate['len'] + 2;
            $addedRelevantParagraph = true;
        }

        // Không đoạn nào khớp topic vừa đủ ngân sách (VD topic không khớp gì trong bài, hoặc
        // ngân sách quá nhỏ chỉ vừa đúng đoạn mở đầu) → không có gì thực sự được "chọn theo liên
        // quan", rơi về cắt chuẩn trên TOÀN VĂN BẢN gốc (không giới hạn trong mỗi đoạn lead) để
        // giữ đúng hành vi mặc định quen thuộc.
        if (! $addedRelevantParagraph) {
            return ['text' => $this->truncateAtBoundary($text, $max), 'relevance_applied' => false];
        }

        if (count($selected) === count($paragraphs)) {
            // Mọi đoạn đều khớp topic và vừa ngân sách — thực chất không lược bỏ gì, không phải
            // trường hợp "chọn lọc theo liên quan" thật sự.
            return ['text' => $this->truncateAtBoundary($text, $max), 'relevance_applied' => false];
        }

        ksort($selected);

        $kept      = [];
        $lastIndex = null;

        foreach (array_keys($selected) as $index) {
            if ($lastIndex !== null && $index > $lastIndex + 1) {
                $kept[] = '[…]';
            }

            $kept[]    = $paragraphs[$index];
            $lastIndex = $index;
        }

        $assembled = implode("\n\n", $kept);

        return [
            'text'              => mb_strlen($assembled) <= $max ? $assembled : $this->truncateAtBoundary($assembled, $max),
            'relevance_applied' => true,
        ];
    }

    /** @return string[] */
    private function extractTopicKeywords(?string $topic): array
    {
        if ($topic === null || trim($topic) === '') {
            return [];
        }

        $words = preg_split('/[\s,;:.!?()"\'-]+/u', mb_strtolower(trim($topic))) ?: [];

        return array_values(array_unique(array_filter(
            $words,
            static fn (string $w) => mb_strlen($w) >= self::MIN_TOPIC_KEYWORD_CHARS
        )));
    }

    /** @param string[] $keywords */
    private function scoreParagraphRelevance(string $paragraph, array $keywords): int
    {
        $lower = mb_strtolower($paragraph);
        $score = 0;

        foreach ($keywords as $keyword) {
            $score += substr_count($lower, $keyword);
        }

        return $score;
    }

    /** Ghi chú cho biết main_content đã được RÚT GỌN THEO ĐỘ LIÊN QUAN tới topic thay vì cắt theo thứ tự xuất hiện — xem selectRelevantContent(). */
    private function appendRelevanceNote(?string $notes, bool $relevanceApplied, ?string $topic): ?string
    {
        if (! $relevanceApplied) {
            return $notes;
        }

        $note = "Nội dung nguồn dài hơn giới hạn dán vào chat AI — đã ưu tiên giữ lại các đoạn liên quan tới chủ đề \"{$topic}\" (kể cả ở giữa/cuối bài) thay vì luôn cắt theo thứ tự xuất hiện; đoạn bị lược bỏ được đánh dấu \"[…]\".";

        return $notes ? "{$notes} {$note}" : $note;
    }

    /**
     * Hash nội dung đã chuẩn hoá (collapse whitespace + lowercase) — CHỈ bắt được trùng lặp gần
     * đúng (VD cùng bài fetch 2 lần, hoặc 2 URL cùng trỏ 1 bài). KHÔNG bắt được 2 bài syndicate
     * nội dung giống ý nhưng câu chữ khác nhau — việc đó cần so sánh ngữ nghĩa (Layer 2), ngoài
     * phạm vi hash thô này.
     */
    private function computeContentHash(string $mainContent): string
    {
        $normalized = mb_strtolower(preg_replace('/\s+/u', ' ', trim($mainContent)));

        return hash('sha256', $normalized);
    }

    /**
     * Tra cache content_hash => url ĐẦU TIÊN thấy nội dung này — bắt được trùng lặp cả TRONG 1
     * batch (2 url cùng batch, xử lý tuần tự nên url sau sẽ thấy cache url trước) lẫn GIỮA các
     * batch khác nhau (memory bền qua nhiều request, đúng tinh thần "Memory Layer" — không phải
     * mảng cục bộ trong request). Cache::add() chỉ ghi khi key CHƯA tồn tại — giữ đúng ngữ nghĩa
     * "url đầu tiên/gốc", không bị url sau ghi đè.
     */
    private function resolveDuplicateOf(string $contentHash, string $url): ?string
    {
        if (! config('core_idea_extractor.cache.enabled', true)) {
            return null;
        }

        $key = 'core_idea_extractor:content_hash:'.$contentHash;
        $ttl = (int) config('core_idea_extractor.cache.content_hash_ttl_seconds', 86400);

        if (Cache::add($key, $url, $ttl)) {
            return null;
        }

        $firstSeenUrl = Cache::get($key);

        return $firstSeenUrl !== $url ? $firstSeenUrl : null;
    }

    /** @param HeadingData[] $headings @param string[] $keywords */
    private function buildResult(
        ?string $title,
        ?string $metaDescription,
        array $keywords,
        array $headings,
        string $mainContent,
        ?string $publishDate,
        ?string $author,
        string $language,
        ExtractionConfidence $confidence,
        ?string $notes,
        int $wordCount = 0,
        int $headingCount = 0,
        ?SourceStructureData $sourceStructure = null,
        ?string $canonicalUrl = null,
        ?string $contentCategory = null,
        ?string $declaredContentType = null,
        ?string $dateModified = null,
        ?string $publisherName = null,
        ?string $contentTypeSignal = null,
        array $sections = [],
        int $rawHtmlChars = 0,
        int $mainContentChars = 0,
        float $reductionPercent = 0.0,
    ): RawExtractionData {
        return new RawExtractionData(
            title: $title,
            meta_description: $metaDescription,
            canonical_url: $canonicalUrl,
            content_category: $contentCategory,
            declared_content_type: $declaredContentType,
            content_type_signal: $contentTypeSignal,
            keywords: $keywords,
            headings: $headings,
            sections: $sections,
            main_content: $mainContent,
            publish_date: $publishDate,
            date_modified: $dateModified,
            author: $author,
            publisher_name: $publisherName,
            language: $language,
            extraction_confidence: $confidence,
            notes: $notes,
            word_count: $wordCount,
            meaningful_heading_count: $headingCount,
            source_structure: $sourceStructure ?? SourceStructureData::none(),
            raw_html_chars: $rawHtmlChars,
            main_content_chars: $mainContentChars,
            reduction_percent: $reductionPercent,
        );
    }

    /**
     * So sánh độ dài HTML gốc (trước parse) với main_content Markdown SAU CÙNG (đã cắt theo ngân
     * sách ký tự nếu có) — số đo THẬT, không phải ước lượng token/4 như `promptSizeWarningText()`
     * ở view (mục đích khác: đó là cảnh báo kích thước PROMPT tổng thể gửi AI, đây là % giảm dung
     * lượng riêng của bước trích xuất HTML→Markdown). 0% khi không có HTML để so (nhánh lỗi fetch).
     *
     * @return array{raw_html_chars: int, main_content_chars: int, reduction_percent: float}
     */
    private function computeContentReduction(int $rawHtmlChars, string $mainContent): array
    {
        $mainContentChars = mb_strlen($mainContent);

        return [
            'raw_html_chars'     => $rawHtmlChars,
            'main_content_chars' => $mainContentChars,
            'reduction_percent'  => $rawHtmlChars > 0 ? round((1 - $mainContentChars / $rawHtmlChars) * 100, 1) : 0.0,
        ];
    }

    /**
     * Selector do người dùng chỉ định nhưng không khớp phần tử nào trên trang → vẫn trả kết quả
     * (đã tự động rơi về resolveContentRoot() mặc định trong ExtractRawContentAction), nhưng
     * thêm ghi chú để người dùng biết selector của họ không được áp dụng.
     */
    private function appendSelectorNote(?string $notes, ?string $selector, ?bool $customSelectorMatched): ?string
    {
        if ($selector === null || trim($selector) === '' || $customSelectorMatched !== false) {
            return $notes;
        }

        $note = "Selector tùy chỉnh \"{$selector}\" không khớp phần tử nào trên trang — đã dùng thuật toán tự động để xác định main_content.";

        return $notes ? "{$note} {$notes}" : $note;
    }

    /**
     * spec/CoreIdeaExtractor.md §13 (v1.5) — tham khảo https://kime.ai/blog/structure-content-for-llm-extraction:
     * nguồn dùng bảng/danh sách số CÙNG heading dạng câu hỏi thường được AI answer engine trích
     * dẫn nhiều hơn văn xuôi — ghi chú advisory để người viết biết nguồn tham khảo đã "tối ưu cho
     * AI search" tới đâu, cân nhắc chọn góc viết khác biệt thay vì lặp lại. Ngưỡng 0.3 (≥ 30%
     * heading dạng câu hỏi) là heuristic nhẹ, không phải số liệu khoa học — chỉ mang tính gợi ý.
     */
    private function appendStructureNote(?string $notes, SourceStructureData $structure): ?string
    {
        $wellStructured = ($structure->has_tables || $structure->has_numbered_lists)
            && $structure->question_heading_ratio >= 0.3;

        if (! $wellStructured) {
            return $notes;
        }

        $note = 'Nguồn có bảng/danh sách số + heading dạng câu hỏi — đã cấu trúc khá tốt cho AI trích xuất (dễ được AI answer engine trích dẫn), cân nhắc chọn góc viết khác biệt thay vì lặp lại thông tin tương tự.';

        return $notes ? "{$notes} {$note}" : $note;
    }

    /**
     * `<html lang>` do site khai báo có thể sai/lỗi thời so với ngôn ngữ THẬT của nội dung (VD
     * site khai `lang="en-US"` nhưng bài viết thực tế bằng tiếng Thái — thường do lỗi cấu hình
     * CMS/template) — xem ExtractRawContentAction::resolveLanguage(). `language` trong response đã
     * được tự động điều chỉnh theo nội dung thật, note này chỉ để người dùng biết field đã bị sai
     * lệch so với khai báo gốc của site, không tự nhiên mà có.
     */
    private function appendLanguageMismatchNote(?string $notes, bool $mismatchSuspected, string $language): ?string
    {
        if (! $mismatchSuspected) {
            return $notes;
        }

        $note = "Ngôn ngữ trang khai báo (<html lang>) không khớp ngôn ngữ thực tế phát hiện được trong nội dung — đã tự động điều chỉnh trường `language` thành \"{$language}\" (có thể do lỗi cấu hình CMS/template của site).";

        return $notes ? "{$notes} {$note}" : $note;
    }

    /**
     * Người dùng dán HTML tay nhưng title trống → nhiều khả năng chỉ dán 1 đoạn fragment (VD
     * riêng nội dung trong `<div class="post__content">`) chứ không dán cả `<head>` — giải thích
     * rõ nguyên nhân thay vì để title/meta trống mà không rõ vì sao (cùng tinh thần với
     * appendSelectorNote()).
     */
    private function appendPastedFragmentNote(?string $notes, bool $pasted, ?string $title): ?string
    {
        if (! $pasted || $title !== null) {
            return $notes;
        }

        $note = 'Không tìm thấy <title>/<head> trong mã HTML đã dán — có thể bạn chỉ dán 1 đoạn fragment (VD riêng nội dung trong div class="post__content"), nên title/meta_description/author sẽ trống. Dán cả trang (View Source) nếu cần đầy đủ các trường này.';

        return $notes ? "{$note} {$notes}" : $note;
    }

    private function truncateMainContent(string $text): string
    {
        $max = (int) config('core_idea_extractor.max_main_content_chars', 20000);

        return $this->truncateAtBoundary($text, $max);
    }

    /**
     * Cắt tại ranh giới câu gần nhất (.!?  theo sau bởi khoảng trắng/hết chuỗi, hoặc xuống dòng)
     * thay vì cắt cứng theo vị trí ký tự — tránh cắt giữa câu/giữa từ, và tránh mất hẳn phần kết
     * luận nếu ranh giới câu nằm ngay trước ngưỡng. Chỉ dùng ranh giới nếu nó giữ được ít nhất
     * 70% ngân sách ký tự yêu cầu — nếu ranh giới gần nhất ở quá xa về đầu (VD nội dung không có
     * dấu câu rõ ràng, danh sách dài...), rơi về cắt cứng để không mất quá nhiều nội dung.
     */
    private function truncateAtBoundary(string $text, int $max): string
    {
        if (mb_strlen($text) <= $max) {
            return $text;
        }

        $window        = mb_substr($text, 0, $max);
        $minAcceptable = (int) ($max * 0.7);
        $cutAt         = null;

        if (preg_match_all('/[.!?](?=\s|$)|\n/u', $window, $matches, PREG_OFFSET_CAPTURE)) {
            [$boundary, $byteOffset] = end($matches[0]);
            $charOffset              = mb_strlen(substr($window, 0, $byteOffset));
            $cutAt                   = $boundary === "\n" ? $charOffset : $charOffset + 1;
        }

        if ($cutAt !== null && $cutAt >= $minAcceptable) {
            return rtrim(mb_substr($text, 0, $cutAt)).'…';
        }

        return $window.'…';
    }
}
