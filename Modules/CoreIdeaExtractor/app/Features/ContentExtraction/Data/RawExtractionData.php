<?php

namespace Modules\CoreIdeaExtractor\Features\ContentExtraction\Data;

use Modules\CoreIdeaExtractor\Enums\ExtractionConfidence;
use Spatie\LaravelData\Data;

/**
 * Kết quả Layer 1 — spec/CoreIdeaExtractor.md §5.2. `word_count`/`meaningful_heading_count`
 * là dữ liệu tính toán nội bộ (dùng bởi ComputeExtractionConfidenceAction), KHÔNG thuộc JSON
 * trả ra ngoài — xem toApiArray() chỉ xuất đúng các field chính thức của §5.2 (đã bổ sung
 * `keywords` từ meta[name=keywords], và `canonical_url`/`content_category`/
 * `declared_content_type`/`date_modified`/`publisher_name` từ OpenGraph/JSON-LD do chính site
 * khai báo — xem ExtractRawContentAction).
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
        public readonly ?string $canonical_url,
        public readonly ?string $content_category,
        public readonly ?string $declared_content_type,
        public readonly ?string $content_type_signal,
        public readonly array $keywords,
        public readonly array $headings,
        public readonly string $main_content,
        public readonly ?string $publish_date,
        public readonly ?string $date_modified,
        public readonly ?string $author,
        public readonly ?string $publisher_name,
        public readonly string $language,
        public readonly ExtractionConfidence $extraction_confidence,
        public readonly ?string $notes,
        public readonly int $word_count,
        public readonly int $meaningful_heading_count,
        public readonly SourceStructureData $source_structure,
    ) {}

    /** @return array{title:?string, meta_description:?string, canonical_url:?string, content_category:?string, declared_content_type:?string, content_type_signal:?string, keywords:array, headings:array, main_content:string, publish_date:?string, date_modified:?string, author:?string, publisher_name:?string, language:string, extraction_confidence:string, notes:?string, source_structure:array} */
    public function toApiArray(): array
    {
        return [
            'title'                  => $this->title,
            'meta_description'       => $this->meta_description,
            'canonical_url'          => $this->canonical_url,
            'content_category'       => $this->content_category,
            'declared_content_type'  => $this->declared_content_type,
            'content_type_signal'    => $this->content_type_signal,
            'keywords'               => $this->keywords,
            'headings'               => array_map(static fn (HeadingData $h) => $h->toArray(), $this->headings),
            'main_content'           => $this->main_content,
            'publish_date'           => $this->publish_date,
            'date_modified'          => $this->date_modified,
            'author'                 => $this->author,
            'publisher_name'         => $this->publisher_name,
            'language'               => $this->language,
            'extraction_confidence'  => $this->extraction_confidence->value,
            'notes'                  => $this->notes,
            'source_structure'       => $this->source_structure->toArray(),
        ];
    }
}
