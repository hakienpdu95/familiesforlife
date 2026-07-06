<?php

namespace Modules\BusinessBlueprint\Features\BlueprintAuthoring\Data;

use Spatie\LaravelData\Data;

/**
 * Kết quả runtime của CompareBlueprintVersionsAction — KHÔNG persist xuống DB
 * (chỉ trả về qua API/JSON response), nên không vi phạm quy tắc "không dùng JSON
 * để lưu data" của module này.
 */
class BlueprintVersionDiff extends Data
{
    public function __construct(
        /** @var array<string, array<int, array<string, mixed>>> */
        public readonly array $added,
        /** @var array<string, array<int, array<string, mixed>>> */
        public readonly array $removed,
        /** @var array<string, array<int, array<string, mixed>>> */
        public readonly array $changed,
    ) {}
}
