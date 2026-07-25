<?php

namespace Modules\CoreIdeaExtractor\Features\ContentExtraction\Http;

use App\Http\Controllers\Controller;
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
use Modules\CoreIdeaExtractor\Features\ContentExtraction\Data\BatchSourceResultData;
use Modules\CoreIdeaExtractor\Features\ContentExtraction\Data\ExtractBatchRequestData;
use Modules\CoreIdeaExtractor\Features\ContentExtraction\Data\ExtractBatchResultData;
use Modules\CoreIdeaExtractor\Features\ContentExtraction\Data\ExtractRequestData;
use Modules\CoreIdeaExtractor\Features\ContentExtraction\Data\HeadingData;
use Modules\CoreIdeaExtractor\Features\ContentExtraction\Data\RawExtractionData;
use Modules\CoreIdeaExtractor\Features\ContentExtraction\Data\SourceStructureData;
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

        $extracted        = $extractRaw->handle($html, $data->main_content_selector);
        $confidenceResult = $computeConfidence->handle($extracted);
        $notes            = $this->appendSelectorNote($confidenceResult['notes'], $data->main_content_selector, $extracted['custom_selector_matched']);
        $notes            = $this->appendPastedFragmentNote($notes, $pasted, $extracted['title']);
        $notes            = $this->appendStructureNote($notes, $extracted['source_structure']);

        $result = $this->buildResult(
            title: $extracted['title'],
            metaDescription: $extracted['meta_description'],
            keywords: $extracted['keywords'],
            headings: $extracted['headings'],
            mainContent: $this->truncateMainContent($extracted['main_content']),
            publishDate: $extracted['publish_date'],
            author: $extracted['author'],
            language: $extracted['language'],
            confidence: $confidenceResult['confidence'],
            notes: $notes,
            wordCount: $extracted['word_count'],
            headingCount: $extracted['meaningful_heading_count'],
            sourceStructure: $extracted['source_structure'],
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
            'force_refresh'          => ['nullable', 'boolean'],
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

            $extracted        = $extractRaw->handle($item['html'], $data->main_content_selector);
            $confidenceResult = $computeConfidence->handle($extracted);
            $notes            = $this->appendSelectorNote($confidenceResult['notes'], $data->main_content_selector, $extracted['custom_selector_matched']);
            $notes            = $this->appendStructureNote($notes, $extracted['source_structure']);
            $mainContent      = $this->truncateBatchMainContent($extracted['main_content']);
            $contentHash      = $this->computeContentHash($mainContent);

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
                    mainContent: $mainContent,
                    publishDate: $extracted['publish_date'],
                    author: $extracted['author'],
                    language: $extracted['language'],
                    confidence: $confidenceResult['confidence'],
                    notes: $notes,
                    wordCount: $extracted['word_count'],
                    headingCount: $extracted['meaningful_heading_count'],
                    sourceStructure: $extracted['source_structure'],
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

    private function resolveDomain(string $url): string
    {
        return parse_url($url, PHP_URL_HOST) ?: $url;
    }

    private function truncateBatchMainContent(string $text): string
    {
        $max = (int) config('core_idea_extractor.batch.max_main_content_chars_per_source', 12000);

        return $this->truncateAtBoundary($text, $max);
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
    ): RawExtractionData {
        return new RawExtractionData(
            title: $title,
            meta_description: $metaDescription,
            keywords: $keywords,
            headings: $headings,
            main_content: $mainContent,
            publish_date: $publishDate,
            author: $author,
            language: $language,
            extraction_confidence: $confidence,
            notes: $notes,
            word_count: $wordCount,
            meaningful_heading_count: $headingCount,
            source_structure: $sourceStructure ?? SourceStructureData::none(),
        );
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
