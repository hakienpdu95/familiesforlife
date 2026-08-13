<?php

namespace Modules\EntityComparison\Features\PublicFiltering\Queries;

use App\Shared\Contracts\QueryInterface;

/**
 * spec/Entity_Comparison_Module_Technical_Spec.md §7.2 bước 1 — trang lọc public, đúng mẫu
 * ListPublicRealEstateListingsQuery (Eloquent range filter thuần, không Meilisearch — §0 mục 13).
 */
class ListEntitiesForPublicQuery implements QueryInterface
{
    /**
     * @param  array<int, mixed>  $criterionFilters  keyed by criterion_id. Hình dạng theo type:
     *                                               text/select/date/boolean → scalar; number/range → ['min' => ?, 'max' => ?];
     *                                               multi_select → int[] (option_id đã chọn, khớp bất kỳ — OR).
     */
    public function __construct(
        public readonly int $entityTypeId,
        public readonly array $criterionFilters = [],
        public readonly int $page = 1,
        public readonly int $perPage = 12,
    ) {}
}
