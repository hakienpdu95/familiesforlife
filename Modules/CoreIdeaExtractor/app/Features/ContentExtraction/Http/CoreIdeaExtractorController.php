<?php

namespace Modules\CoreIdeaExtractor\Features\ContentExtraction\Http;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\CoreIdeaExtractor\Enums\ExtractionConfidence;
use Modules\CoreIdeaExtractor\Features\ContentExtraction\Actions\ComputeExtractionConfidenceAction;
use Modules\CoreIdeaExtractor\Features\ContentExtraction\Actions\ExtractRawContentAction;
use Modules\CoreIdeaExtractor\Features\ContentExtraction\Actions\FetchArticleHtmlAction;
use Modules\CoreIdeaExtractor\Features\ContentExtraction\Data\ExtractRequestData;
use Modules\CoreIdeaExtractor\Features\ContentExtraction\Data\HeadingData;
use Modules\CoreIdeaExtractor\Features\ContentExtraction\Data\RawExtractionData;
use Modules\CoreIdeaExtractor\Features\ContentExtraction\Exceptions\UrlFetchException;

class CoreIdeaExtractorController extends Controller
{
    public function index(): View
    {
        return view('coreideaextractor::index');
    }

    /**
     * Chạy đồng bộ trong request (KHÔNG queue) — Layer 1 chỉ fetch+parse HTML, không có lệnh
     * gọi AI 5-30s nào cần chạy nền (khác Aicem). Lỗi fetch (mạng/SSRF/HTTP status/content-type)
     * KHÔNG phải lỗi validate — vẫn trả 200 với extraction_confidence=low + notes rõ nguyên
     * nhân, đúng tinh thần "luôn trả kết quả có cấu trúc" của spec §1.
     */
    public function extract(Request $request, FetchArticleHtmlAction $fetch, ExtractRawContentAction $extractRaw, ComputeExtractionConfidenceAction $computeConfidence): JsonResponse
    {
        $data = ExtractRequestData::from($request->validate([
            'url' => ['required', 'url', 'max:2048'],
        ]));

        try {
            $html = $fetch->handle($data->url);
        } catch (UrlFetchException $e) {
            return response()->json($this->buildResult(
                title: null,
                metaDescription: null,
                headings: [],
                mainContent: '',
                publishDate: null,
                author: null,
                language: 'unknown',
                confidence: ExtractionConfidence::Low,
                notes: $e->getMessage(),
            )->toApiArray());
        }

        $extracted        = $extractRaw->handle($html);
        $confidenceResult = $computeConfidence->handle($extracted);

        $result = $this->buildResult(
            title: $extracted['title'],
            metaDescription: $extracted['meta_description'],
            headings: $extracted['headings'],
            mainContent: $this->truncateMainContent($extracted['main_content']),
            publishDate: $extracted['publish_date'],
            author: $extracted['author'],
            language: $extracted['language'],
            confidence: $confidenceResult['confidence'],
            notes: $confidenceResult['notes'],
            wordCount: $extracted['word_count'],
            headingCount: $extracted['meaningful_heading_count'],
        );

        return response()->json($result->toApiArray());
    }

    /** @param HeadingData[] $headings */
    private function buildResult(
        ?string $title,
        ?string $metaDescription,
        array $headings,
        string $mainContent,
        ?string $publishDate,
        ?string $author,
        string $language,
        ExtractionConfidence $confidence,
        ?string $notes,
        int $wordCount = 0,
        int $headingCount = 0,
    ): RawExtractionData {
        return new RawExtractionData(
            title: $title,
            meta_description: $metaDescription,
            headings: $headings,
            main_content: $mainContent,
            publish_date: $publishDate,
            author: $author,
            language: $language,
            extraction_confidence: $confidence,
            notes: $notes,
            word_count: $wordCount,
            meaningful_heading_count: $headingCount,
        );
    }

    private function truncateMainContent(string $text): string
    {
        $max = (int) config('core_idea_extractor.max_main_content_chars', 20000);

        return mb_strlen($text) > $max ? mb_substr($text, 0, $max).'…' : $text;
    }
}
