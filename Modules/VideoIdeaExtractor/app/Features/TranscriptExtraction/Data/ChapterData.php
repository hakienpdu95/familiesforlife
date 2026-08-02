<?php

namespace Modules\VideoIdeaExtractor\Features\TranscriptExtraction\Data;

use Spatie\LaravelData\Data;

/**
 * Tương đương HeadingData bên CoreIdeaExtractor nhưng cho transcript — `time` là timestamp gốc
 * (VD "12:34" hoặc "1:02:34") lấy nguyên văn từ dòng transcript, `text` là tên chương ngay sau đó.
 * Xem ExtractTranscriptAction::extractChapters().
 */
class ChapterData extends Data
{
    public function __construct(
        public readonly string $time,
        public readonly string $text,
    ) {}
}
