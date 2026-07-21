<?php

namespace Modules\ContentBrief\Features\Generation\Data;

use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Data;

/**
 * spec/ContentBrief_Technical_Specification.md §6.1 — cấu trúc CHUẨN của `output`, đây là
 * hợp đồng dữ liệu duy nhất mà bất kỳ cơ chế sinh nội dung nào (nằm ngoài phạm vi module) phải
 * tuân theo khi gọi CompleteBriefGenerationAction. Validate thất bại → từ chối lưu, không có
 * "lưu một phần".
 */
class GenerationOutputData extends Data
{
    public function __construct(
        #[Required, Max(300)]
        public readonly string $title,

        public readonly ?string $meta_description = null,

        /** @var GenerationSectionData[] */
        #[DataCollectionOf(GenerationSectionData::class)]
        public readonly array $sections = [],

        public readonly ?int $word_count = null,

        /** @var string[] */
        public readonly array $seo_keywords_used = [],
    ) {}
}
