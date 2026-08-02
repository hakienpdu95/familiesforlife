<?php

namespace Modules\VideoIdeaExtractor\Features\TranscriptExtraction\Data;

use Modules\VideoIdeaExtractor\Enums\TranscriptConfidence;
use Spatie\LaravelData\Data;

/**
 * Kết quả Layer 1 cho 1 video — tương đương RawExtractionData bên CoreIdeaExtractor.
 * `chapters` là tín hiệu BỔ SUNG (như `headings`, KHÔNG như `sections` — sections bên
 * CoreIdeaExtractor chứa TOÀN VĂN từng mục, còn ở đây `chapters[].text` chỉ là tên chương/mốc thời
 * gian, không chứa nội dung nói giữa 2 mốc) — `transcript` LUÔN có mặt đầy đủ, không bị thay thế
 * khi có chapters.
 */
class RawTranscriptData extends Data
{
    /** @param ChapterData[] $chapters */
    public function __construct(
        public readonly string $title,
        public readonly array $chapters,
        public readonly string $transcript,
        public readonly int $word_count,
        public readonly TranscriptConfidence $extraction_confidence,
        public readonly ?string $notes,
    ) {}

    /** @return array{title:string, chapters:array, transcript:string, word_count:int, extraction_confidence:string, notes:?string} */
    public function toApiArray(): array
    {
        return [
            'title'                 => $this->title,
            'chapters'              => array_map(static fn (ChapterData $c) => $c->toArray(), $this->chapters),
            'transcript'            => $this->transcript,
            'word_count'            => $this->word_count,
            'extraction_confidence' => $this->extraction_confidence->value,
            'notes'                 => $this->notes,
        ];
    }
}
