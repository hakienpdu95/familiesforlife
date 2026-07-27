<?php

namespace Modules\CoreIdeaExtractor\Features\ContentExtraction\Data;

use Spatie\LaravelData\Data;

/**
 * Kết quả 1 nguồn trong batch — 1 SHAPE DUY NHẤT cho mọi status (success/blocked/error), field
 * không áp dụng thì null có chủ đích (không dùng chuỗi rỗng) — để downstream (kể cả AI đọc JSON
 * này) không phải rẽ nhánh theo status mới biết field nào tồn tại. `keywords`/`headings` vẫn
 * dùng mảng rỗng `[]` khi không có dữ liệu (không phải null) vì đây là list, không phải scalar.
 *
 * `duplicate_of`: url ĐẦU TIÊN (có thể từ batch trước, khác request) có cùng content_hash đã
 * chuẩn hoá — phát hiện qua cache cross-reference (xem CoreIdeaExtractorController), không phải
 * so sánh trong bộ nhớ của riêng batch này. null nếu url này là url đầu tiên có nội dung đó.
 *
 * `sections` (v1.14): cũng dùng mảng rỗng `[]` khi không có heading nào để tách (cùng quy ước với
 * `keywords`/`headings`) — xem ExtractRawContentAction::buildSections().
 */
class BatchSourceResultData extends Data
{
    /**
     * @param  string[]  $keywords
     * @param  array<int, array{level: int, text: string}>  $headings
     * @param  array<int, array{heading: ?string, text: string}>  $sections
     */
    public function __construct(
        public readonly string $url,
        public readonly ?string $final_url,
        public readonly string $domain,
        public readonly string $status,
        public readonly ?string $failure_type,
        public readonly ?int $http_status,
        public readonly ?string $title,
        public readonly ?string $meta_description,
        public readonly ?string $canonical_url,
        public readonly ?string $content_category,
        public readonly ?string $declared_content_type,
        public readonly ?string $content_type_signal,
        public readonly array $keywords,
        public readonly array $headings,
        public readonly array $sections,
        public readonly ?string $main_content,
        public readonly ?string $content_hash,
        public readonly ?string $duplicate_of,
        public readonly ?int $word_count,
        public readonly ?string $publish_date,
        public readonly ?string $date_modified,
        public readonly ?string $author,
        public readonly ?string $publisher_name,
        public readonly ?string $language,
        public readonly ?string $extraction_confidence,
        public readonly ?string $notes,
        public readonly ?string $error_message,
        public readonly string $fetched_at,
        public readonly ?array $source_structure,
    ) {}

    public static function success(
        string $url,
        ?string $finalUrl,
        string $domain,
        ?int $httpStatus,
        RawExtractionData $extraction,
        string $contentHash,
        ?string $duplicateOf,
        string $fetchedAt,
    ): self {
        return new self(
            url: $url,
            final_url: $finalUrl,
            domain: $domain,
            status: 'success',
            failure_type: null,
            http_status: $httpStatus,
            title: $extraction->title,
            meta_description: $extraction->meta_description,
            canonical_url: $extraction->canonical_url,
            content_category: $extraction->content_category,
            declared_content_type: $extraction->declared_content_type,
            content_type_signal: $extraction->content_type_signal,
            keywords: $extraction->keywords,
            headings: array_map(
                static fn (HeadingData $h) => ['level' => $h->level, 'text' => $h->text],
                $extraction->headings,
            ),
            sections: $extraction->sections,
            main_content: $extraction->main_content,
            content_hash: $contentHash,
            duplicate_of: $duplicateOf,
            word_count: $extraction->word_count,
            publish_date: $extraction->publish_date,
            date_modified: $extraction->date_modified,
            author: $extraction->author,
            publisher_name: $extraction->publisher_name,
            language: $extraction->language,
            extraction_confidence: $extraction->extraction_confidence->value,
            notes: $extraction->notes,
            error_message: null,
            fetched_at: $fetchedAt,
            source_structure: $extraction->source_structure->toArray(),
        );
    }

    public static function failure(
        string $url,
        ?string $finalUrl,
        string $domain,
        string $status,
        string $failureType,
        ?int $httpStatus,
        string $errorMessage,
        string $fetchedAt,
    ): self {
        return new self(
            url: $url,
            final_url: $finalUrl,
            domain: $domain,
            status: $status,
            failure_type: $failureType,
            http_status: $httpStatus,
            title: null,
            meta_description: null,
            canonical_url: null,
            content_category: null,
            declared_content_type: null,
            content_type_signal: null,
            keywords: [],
            headings: [],
            sections: [],
            main_content: null,
            content_hash: null,
            duplicate_of: null,
            word_count: null,
            publish_date: null,
            date_modified: null,
            author: null,
            publisher_name: null,
            language: null,
            extraction_confidence: null,
            notes: null,
            error_message: $errorMessage,
            fetched_at: $fetchedAt,
            source_structure: null,
        );
    }

    public function toApiArray(): array
    {
        return [
            'url'                    => $this->url,
            'final_url'              => $this->final_url,
            'domain'                 => $this->domain,
            'status'                 => $this->status,
            'failure_type'           => $this->failure_type,
            'http_status'            => $this->http_status,
            'title'                  => $this->title,
            'meta_description'       => $this->meta_description,
            'canonical_url'          => $this->canonical_url,
            'content_category'       => $this->content_category,
            'declared_content_type'  => $this->declared_content_type,
            'content_type_signal'    => $this->content_type_signal,
            'keywords'               => $this->keywords,
            'headings'               => $this->headings,
            'sections'               => $this->sections,
            'main_content'           => $this->main_content,
            'content_hash'           => $this->content_hash,
            'duplicate_of'           => $this->duplicate_of,
            'word_count'             => $this->word_count,
            'publish_date'           => $this->publish_date,
            'date_modified'          => $this->date_modified,
            'author'                 => $this->author,
            'publisher_name'         => $this->publisher_name,
            'language'               => $this->language,
            'extraction_confidence'  => $this->extraction_confidence,
            'notes'                  => $this->notes,
            'error_message'          => $this->error_message,
            'fetched_at'             => $this->fetched_at,
            'source_structure'       => $this->source_structure,
        ];
    }
}
