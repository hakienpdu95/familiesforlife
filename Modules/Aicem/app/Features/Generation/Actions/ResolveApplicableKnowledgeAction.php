<?php

namespace Modules\Aicem\Features\Generation\Actions;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Aicem\Enums\ScopeMatch;
use Modules\Aicem\Models\AicemKnowledgeDocument;

/**
 * Thuật toán chọn document theo scope — spec/AICEM_Technical_Specification.md mục 6.7 + input
 * bounding mục 6.9.1 (max_docs_per_type, bỏ document priority THẤP NHẤT trước khi vượt trần).
 */
class ResolveApplicableKnowledgeAction
{
    use AsAction;

    /** @return array{content: string[], truncated: bool} */
    public function handle(int $organizationId, string $type, ?string $subjectType, array $taxonomy): array
    {
        $docs = AicemKnowledgeDocument::query()
            ->where('organization_id', $organizationId)
            ->where('type', $type)
            ->where(function ($q) use ($subjectType) {
                $q->whereNull('subject_type')->orWhere('subject_type', $subjectType);
            })
            ->get()
            ->filter(fn (AicemKnowledgeDocument $doc) => $this->matchesScope($doc, $taxonomy))
            ->sortBy('priority')
            ->values();

        $maxDocs    = config('aicem.prompt_bounds.max_docs_per_type', 5);
        $truncated  = false;

        if ($docs->count() > $maxDocs) {
            // priority tăng dần (general trước, specific sau) → "thấp nhất" nằm ở ĐẦU danh sách,
            // slice(-$maxDocs) giữ lại phần CUỐI (priority cao nhất/specific nhất), bỏ phần đầu.
            $docs      = $docs->slice(-$maxDocs)->values();
            $truncated = true;
        }

        return [
            'content'   => $docs->pluck('content')->all(),
            'truncated' => $truncated,
        ];
    }

    private function matchesScope(AicemKnowledgeDocument $doc, array $taxonomy): bool
    {
        if ($doc->scope === null) {
            return true;
        }

        $matches = [];
        foreach ($doc->scope as $key => $values) {
            $taxonomyValues = $taxonomy[$key] ?? null;
            $matches[] = $taxonomyValues !== null
                && count(array_intersect((array) $values, (array) $taxonomyValues)) > 0;
        }

        if (empty($matches)) {
            return true;
        }

        return $doc->scope_match === ScopeMatch::All
            ? ! in_array(false, $matches, true)
            : in_array(true, $matches, true);
    }
}
