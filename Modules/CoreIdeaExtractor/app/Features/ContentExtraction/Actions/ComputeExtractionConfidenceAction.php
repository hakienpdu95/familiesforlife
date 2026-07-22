<?php

namespace Modules\CoreIdeaExtractor\Features\ContentExtraction\Actions;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\CoreIdeaExtractor\Enums\ExtractionConfidence;

/**
 * spec/CoreIdeaExtractor.md §5.4 (v1.2) — mốc DUY NHẤT: main_content < 200 từ luôn là `low`
 * (không còn vùng xám 150-199 từ). Module này KHÔNG chạy Layer 2 nên không có `error` field/
 * thin-content-trigger nào cần phản ánh ra output — `notes` chỉ mô tả chất lượng Layer 1.
 */
class ComputeExtractionConfidenceAction
{
    use AsAction;

    /**
     * @param array{title:?string, meaningful_heading_count:int, word_count:int, paywall_suspected:bool} $extracted
     * @return array{confidence: ExtractionConfidence, notes: ?string}
     */
    public function handle(array $extracted): array
    {
        $hasTitle         = ! empty(trim((string) ($extracted['title'] ?? '')));
        $headingCount     = $extracted['meaningful_heading_count'] ?? 0;
        $wordCount        = $extracted['word_count'] ?? 0;
        $paywallSuspected = $extracted['paywall_suspected'] ?? false;

        $highMinWords    = (int) config('core_idea_extractor.confidence.high_min_words', 400);
        $highMinHeadings = (int) config('core_idea_extractor.confidence.high_min_headings', 2);
        $mediumMinWords  = (int) config('core_idea_extractor.confidence.medium_min_words', 200);
        $errorMaxWords   = (int) config('core_idea_extractor.confidence.error_max_words', 150);

        if ($hasTitle && $headingCount >= $highMinHeadings && $wordCount >= $highMinWords) {
            $confidence = ExtractionConfidence::High;
        } elseif ($hasTitle && $wordCount >= $mediumMinWords) {
            $confidence = ExtractionConfidence::Medium;
        } else {
            $confidence = ExtractionConfidence::Low;
        }

        return [
            'confidence' => $confidence,
            'notes'      => $this->buildNotes($confidence, $hasTitle, $wordCount, $errorMaxWords, $paywallSuspected),
        ];
    }

    private function buildNotes(
        ExtractionConfidence $confidence,
        bool $hasTitle,
        int $wordCount,
        int $errorMaxWords,
        bool $paywallSuspected,
    ): ?string {
        $parts = [];

        if ($confidence === ExtractionConfidence::Low) {
            if (! $hasTitle) {
                $parts[] = 'Không lấy được tiêu đề bài viết.';
            }

            if ($wordCount < $errorMaxWords) {
                $parts[] = "Nội dung quá ngắn/rời rạc (~{$wordCount} từ) — hầu như không trích được gì đáng kể.";
            } elseif ($wordCount < 200) {
                $parts[] = "Nội dung chưa đủ dày (~{$wordCount} từ, cần tối thiểu 200 từ).";
            }
        }

        if ($paywallSuspected) {
            $parts[] = 'Có dấu hiệu trang yêu cầu đăng nhập/trả phí (paywall) — nội dung trích được có thể chỉ là phần preview công khai.';
        }

        return $parts === [] ? null : implode(' ', $parts);
    }
}
