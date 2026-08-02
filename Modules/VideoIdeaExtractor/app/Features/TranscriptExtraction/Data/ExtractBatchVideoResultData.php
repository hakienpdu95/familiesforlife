<?php

namespace Modules\VideoIdeaExtractor\Features\TranscriptExtraction\Data;

use Spatie\LaravelData\Data;

/**
 * Envelope cấp batch — tương đương ExtractBatchResultData bên CoreIdeaExtractor §7.1, nhưng đơn
 * giản hơn NHIỀU: không có source_coverage/summary_note/status per-video (không có khái niệm
 * "blocked"/"error" — transcript dán tay chỉ có thể thiếu, và validate() ở controller đã chặn
 * trường hợp đó trước khi tới đây, không cần model hoá lại ở response).
 */
class ExtractBatchVideoResultData extends Data
{
    /** @param RawTranscriptData[] $videos */
    public function __construct(
        public readonly ?string $topic,
        public readonly ?string $audience,
        public readonly ?string $goal,
        public readonly ?string $constraints,
        public readonly ?string $style_sample,
        public readonly int $requested_count,
        public readonly array $videos,
        public readonly string $processed_at,
    ) {}

    /** @return array{topic:?string, brief:array, requested_count:int, videos:array, processed_at:string} */
    public function toApiArray(): array
    {
        return [
            'topic' => $this->topic,
            'brief' => [
                'audience'     => $this->audience,
                'goal'         => $this->goal,
                'constraints'  => $this->constraints,
                'style_sample' => $this->style_sample,
            ],
            'requested_count' => $this->requested_count,
            'videos'          => array_map(static fn (RawTranscriptData $v) => $v->toApiArray(), $this->videos),
            'processed_at'    => $this->processed_at,
        ];
    }
}
