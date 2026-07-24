<?php

namespace Modules\CoreIdeaExtractor\Features\ContentExtraction\Data;

use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Data;

class ExtractBatchRequestData extends Data
{
    /**
     * @param  string[]  $urls  Validate mảng ở controller (required|array|min:1|max:core_idea_extractor.batch.max_urls,
     *   urls.* => url|distinct) — rule động theo config nên không khai bằng attribute Spatie tĩnh.
     */
    public function __construct(
        public readonly array $urls,
        /** Từ khóa nghiên cứu do người dùng nhập — thuần metadata, echo lại trong response để nhận diện payload khi dán vào chat AI, KHÔNG dùng để thay đổi logic fetch/extract. */
        #[Nullable, Max(255)]
        public readonly ?string $topic = null,
        /** Áp dụng chung cho mọi URL trong batch — xem ExtractRequestData::$main_content_selector. */
        #[Nullable, Max(255)]
        public readonly ?string $main_content_selector = null,
    ) {}
}
