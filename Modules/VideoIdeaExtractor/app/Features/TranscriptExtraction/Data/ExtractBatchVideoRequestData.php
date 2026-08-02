<?php

namespace Modules\VideoIdeaExtractor\Features\TranscriptExtraction\Data;

use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Data;

/**
 * Tương đương ExtractBatchRequestData bên CoreIdeaExtractor — khác ở chỗ `videos` là mảng cặp
 * {title, transcript} (validate ở controller: required|array|min:1|max:video_idea_extractor.
 * batch.max_videos, mỗi phần tử title/transcript riêng) thay vì mảng URL đơn giản, và KHÔNG có
 * main_content_selector(s)/force_refresh/source_language — những field đó chỉ có ý nghĩa khi có
 * bước fetch/parse HTML, không áp dụng cho transcript dán tay.
 */
class ExtractBatchVideoRequestData extends Data
{
    /**
     * @param array<int, array{title: string, transcript: string}> $videos
     */
    public function __construct(
        public readonly array $videos,
        /** Từ khoá nghiên cứu do người dùng nhập — echo lại trong response, thuần metadata. */
        #[Nullable, Max(255)]
        public readonly ?string $topic = null,
        #[Nullable, Max(500)]
        public readonly ?string $audience = null,
        #[Nullable, Max(2000)]
        public readonly ?string $goal = null,
        #[Nullable, Max(500)]
        public readonly ?string $constraints = null,
        #[Nullable, Max(3000)]
        public readonly ?string $style_sample = null,
    ) {}
}
