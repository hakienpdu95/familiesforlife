<?php

namespace Modules\Aicem\Features\KnowledgeBase\Data;

use Spatie\LaravelData\Data;

class KnowledgeDocumentData extends Data
{
    public function __construct(
        public readonly string $type,
        public readonly string $title,
        public readonly string $content,
        public readonly ?string $subject_type = null,
        public readonly ?array $scope = null,
        public readonly string $scope_match = 'any',
        public readonly ?int $priority = null,
    ) {}
}
