<?php

namespace Modules\ContentBrief\Features\BriefManagement\Data;

use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Data;

/**
 * spec/ContentBrief_Technical_Specification.md §0 — generic HOÀN TOÀN, không gắn với 1 module
 * cụ thể nào (vd Modules/Product). `type` là chuỗi tự do do người dùng ghi chú, KHÔNG validate
 * theo model/bảng nào — Content Brief không biết và không quan tâm `type` ứng với thực thể gì.
 */
class BriefRelatedReferenceData extends Data
{
    public function __construct(
        #[Required]
        public readonly string $type,
        #[Required]
        public readonly int|string $id,
        public readonly ?string $label = null,
    ) {}
}
