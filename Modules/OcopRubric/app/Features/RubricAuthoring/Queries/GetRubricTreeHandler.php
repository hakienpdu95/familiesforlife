<?php

namespace Modules\OcopRubric\Features\RubricAuthoring\Queries;

use App\Shared\Contracts\QueryHandlerInterface;
use App\Shared\Contracts\QueryInterface;
use Modules\OcopRubric\Models\OcopRubricVersion;

/**
 * Load toàn bộ cây tiêu chí (sections → mục → tiêu chí lá → options) của 1
 * version, bất kể độ sâu — dùng cho màn hình admin xây cây kéo-thả.
 */
class GetRubricTreeHandler implements QueryHandlerInterface
{
    public function handle(QueryInterface $query): OcopRubricVersion
    {
        /** @var GetRubricTreeQuery $query */
        return OcopRubricVersion::with([
            'productGroup',
            'disqualifiers',
            'sections' => fn ($q) => $q->orderBy('sort_order'),
            'sections.criteria' => fn ($q) => $q->whereNull('parent_id')->orderBy('sort_order'),
            'sections.criteria.childrenRecursive',
            'sections.criteria.options',
        ])->findOrFail($query->rubricVersionId);
    }
}
