<?php

namespace Modules\CoreIdeaExtractor\Features\ContentExtraction\Data;

use Spatie\LaravelData\Data;

class ExtractBatchResultData extends Data
{
    /** @param BatchSourceResultData[] $sources */
    public function __construct(
        public readonly ?string $topic,
        public readonly string $generated_at,
        public readonly int $total_requested,
        public readonly int $successful,
        public readonly int $blocked,
        public readonly int $failed,
        public readonly array $sources,
    ) {}

    public function toApiArray(): array
    {
        return [
            'topic'           => $this->topic,
            'generated_at'    => $this->generated_at,
            'source_coverage' => [
                'total_requested' => $this->total_requested,
                'successful'      => $this->successful,
                'blocked'         => $this->blocked,
                'failed'          => $this->failed,
            ],
            'sources' => array_map(
                static fn (BatchSourceResultData $s) => $s->toApiArray(),
                $this->sources,
            ),
        ];
    }
}
