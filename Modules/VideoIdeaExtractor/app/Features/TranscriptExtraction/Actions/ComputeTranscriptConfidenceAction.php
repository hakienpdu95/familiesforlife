<?php

namespace Modules\VideoIdeaExtractor\Features\TranscriptExtraction\Actions;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\VideoIdeaExtractor\Enums\TranscriptConfidence;

/**
 * Rút gọn ComputeExtractionConfidenceAction bên CoreIdeaExtractor — bỏ hẳn khái niệm
 * paywall_suspected/heading count (không áp dụng cho transcript, không có gì để lấy tín hiệu ngoài
 * độ dài). Ngưỡng cao hơn CoreIdeaExtractor (`high_min_words`/`medium_min_words`) vì transcript nói
 * thường dài hơn bài viết cùng nội dung do văn nói lặp/đệm từ ("ừm", "thì", lặp ý).
 */
class ComputeTranscriptConfidenceAction
{
    use AsAction;

    /** @return array{confidence: TranscriptConfidence, notes: ?string} */
    public function handle(int $wordCount): array
    {
        $highMinWords   = (int) config('video_idea_extractor.confidence.high_min_words', 800);
        $mediumMinWords = (int) config('video_idea_extractor.confidence.medium_min_words', 150);

        if ($wordCount >= $highMinWords) {
            $confidence = TranscriptConfidence::High;
        } elseif ($wordCount >= $mediumMinWords) {
            $confidence = TranscriptConfidence::Medium;
        } else {
            $confidence = TranscriptConfidence::Low;
        }

        $notes = $confidence === TranscriptConfidence::Low
            ? "Transcript quá ngắn (~{$wordCount} từ) — có thể chỉ là đoạn trích, không đủ để sinh ý tưởng chất lượng."
            : null;

        return ['confidence' => $confidence, 'notes' => $notes];
    }
}
