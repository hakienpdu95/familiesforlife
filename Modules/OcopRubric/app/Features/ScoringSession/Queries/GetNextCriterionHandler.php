<?php

namespace Modules\OcopRubric\Features\ScoringSession\Queries;

use App\Shared\Contracts\QueryHandlerInterface;
use App\Shared\Contracts\QueryInterface;
use Modules\OcopRubric\Models\OcopRubricCriterion;
use Modules\OcopRubric\Models\OcopScoringSession;

/**
 * Tiêu chí lá (is_scorable=true) tiếp theo CHƯA có answer, theo đúng thứ tự
 * hiển thị trên deck (Phần A→B→C, trong mỗi Phần theo sort_order, duyệt cây
 * theo chiều sâu). Trả null khi đã trả lời hết.
 */
class GetNextCriterionHandler implements QueryHandlerInterface
{
    public function handle(QueryInterface $query): ?OcopRubricCriterion
    {
        /** @var GetNextCriterionQuery $query */
        $session = OcopScoringSession::with([
            'rubricVersion.sections' => fn ($q) => $q->orderBy('sort_order'),
            'rubricVersion.sections.criteria' => fn ($q) => $q->whereNull('parent_id')->orderBy('sort_order'),
            'rubricVersion.sections.criteria.options',
            'rubricVersion.sections.criteria.childrenRecursive',
        ])->findOrFail($query->sessionId);

        $answeredIds = $session->answers()->pluck('criterion_id')->all();

        foreach ($session->rubricVersion->sections as $section) {
            foreach ($section->criteria as $root) {
                $next = $this->findNextUnanswered($root, $answeredIds);
                if ($next) {
                    return $next;
                }
            }
        }

        return null;
    }

    private function findNextUnanswered(OcopRubricCriterion $node, array $answeredIds): ?OcopRubricCriterion
    {
        if ($node->is_scorable) {
            return in_array($node->id, $answeredIds, true) ? null : $node;
        }

        foreach ($node->childrenRecursive as $child) {
            $found = $this->findNextUnanswered($child, $answeredIds);
            if ($found) {
                return $found;
            }
        }

        return null;
    }
}
