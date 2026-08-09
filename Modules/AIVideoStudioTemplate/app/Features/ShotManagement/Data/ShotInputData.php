<?php

namespace Modules\AIVideoStudioTemplate\Features\ShotManagement\Data;

use Spatie\LaravelData\Data;

/**
 * spec/AIVideoStudioTemplate_Technical_Specification.md §3.2/§7 — DTO input dùng chung cho
 * CreateShotAction/UpdateShotAction. Validate thật nằm ở Store/UpdateShotRequest::rules() — DTO
 * này chỉ hydrate dữ liệu đã qua validate (cùng pattern ContentOutlineInputData).
 */
class ShotInputData extends Data
{
    public function __construct(
        public readonly ?int $sortOrder = null,
        public readonly ?string $label = null,
        public readonly ?string $subject = null,
        public readonly ?string $action = null,
        public readonly ?string $environment = null,
        public readonly ?string $camera = null,
        public readonly ?string $style = null,
        public readonly ?string $mood = null,
        public readonly ?int $durationSeconds = null,
        public readonly ?string $audioDirection = null,
        public readonly ?string $constraints = null,
        public readonly ?string $scriptLine = null,
        public readonly ?string $ctaText = null,
        public readonly ?string $modelTool = null,
        public readonly ?string $referenceAssets = null,
        public readonly ?string $qcNotes = null,
    ) {}
}
