<?php

namespace Modules\AIVideoStudioTemplate\Features\ProjectManagement\Data;

use Spatie\LaravelData\Data;

/**
 * spec/AIVideoStudioTemplate_Technical_Specification.md §7 — DTO input dùng chung cho
 * CreateProjectAction/UpdateProjectAction. `status` chỉ đổi được qua form edit (create luôn khởi
 * tạo 'draft' theo default của Model, §4.1) — validate thật nằm ở Store/UpdateProjectRequest.
 */
class ProjectInputData extends Data
{
    public function __construct(
        public readonly string $name,
        public readonly ?string $description = null,
        public readonly ?string $objective = null,
        public readonly ?string $targetAudience = null,
        public readonly ?string $videoType = null,
        public readonly ?string $coreMessage = null,
        public readonly ?string $aspectRatio = null,
        public readonly ?string $resolution = null,
        public readonly ?string $defaultSubject = null,
        public readonly ?string $referenceImageUrl = null,
        public readonly ?string $referenceContextPrompt = null,
        public readonly ?string $defaultStyle = null,
        public readonly ?string $defaultConstraints = null,
        public readonly ?string $status = null,
    ) {}
}
