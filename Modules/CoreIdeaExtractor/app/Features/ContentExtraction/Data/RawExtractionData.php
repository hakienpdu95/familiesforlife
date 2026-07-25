<?php

namespace Modules\CoreIdeaExtractor\Features\ContentExtraction\Data;

use Modules\CoreIdeaExtractor\Enums\ExtractionConfidence;
use Spatie\LaravelData\Data;

/**
 * Kết quả Layer 1 — spec/CoreIdeaExtractor.md §5.2. `word_count`/`meaningful_heading_count`
 * là dữ liệu tính toán nội bộ (dùng bởi ComputeExtractionConfidenceAction), KHÔNG thuộc JSON
 * trả ra ngoài — xem toApiArray() chỉ xuất đúng các field chính thức của §5.2 (đã bổ sung
 * `keywords` từ meta[name=keywords]).
 */
class RawExtractionData extends Data
{
    /**
     * @param HeadingData[] $headings
     * @param string[] $keywords
     */
    public function __construct(
        public readonly ?string $title,
        public readonly ?string $meta_description,
        public readonly array $keywords,
        public readonly array $headings,
        public readonly string $main_content,
        public readonly ?string $publish_date,
        public readonly ?string $author,
        public readonly string $language,
        public readonly ExtractionConfidence $extraction_confidence,
        public readonly ?string $notes,
        public readonly int $word_count,
        public readonly int $meaningful_heading_count,
        public readonly SourceStructureData $source_structure,
    ) {}

    /** @return array{title:?string, meta_description:?string, keywords:array, headings:array, main_content:string, publish_date:?string, author:?string, language:string, extraction_confidence:string, notes:?string, source_structure:array} */
    public function toApiArray(): array
    {
        return [
            'title'                 => $this->title,
            'meta_description'      => $this->meta_description,
            'keywords'              => $this->keywords,
            'headings'              => array_map(static fn (HeadingData $h) => $h->toArray(), $this->headings),
            'main_content'          => $this->main_content,
            'publish_date'          => $this->publish_date,
            'author'                => $this->author,
            'language'              => $this->language,
            'extraction_confidence' => $this->extraction_confidence->value,
            'notes'                 => $this->notes,
            'source_structure'      => $this->source_structure->toArray(),
        ];
    }
}
