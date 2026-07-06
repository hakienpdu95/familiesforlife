<?php

namespace Modules\OcopRubric\Features\RubricAuthoring\Queries;

use App\Shared\Contracts\QueryHandlerInterface;
use App\Shared\Contracts\QueryInterface;
use Modules\OcopRubric\Models\OcopRubricCriterion;
use Modules\OcopRubric\Models\OcopRubricVersion;

/**
 * Validate toàn vẹn cây tiêu chí trước khi publish (spec §8.1) — bắt buộc chạy
 * mỗi lần publish, không tin tưởng người nhập liệu. 2 bất biến phải giữ:
 *   1. Tổng max_score của 3 phần A/B/C phải = total_max_score (mặc định 100).
 *   2. Tổng max_score của các Mục gốc (root) trong 1 Phần phải = max_score của Phần đó.
 *   3. Mỗi container (is_scorable=false): tổng max_score của con phải = max_score cha.
 *   4. Mỗi tiêu chí lá (is_scorable=true): option cao nhất không được vượt max_score.
 *
 * Lưu ý bất biến #2: các Phần A/B/C có max_score cố định (40/25/35) và không có
 * route nào cho phép sửa trực tiếp — nên bất biến #1 (tổng 3 Phần = 100) gần như
 * luôn đúng một cách "tầm thường" và KHÔNG bắt được lỗi nếu tổng các Mục gốc bên
 * trong 1 Phần bị lệch so với max_score của chính Phần đó. Vì vậy phải validate
 * riêng bất biến #2 — coi Phần như 1 container ở tầng trên cùng của cây.
 */
class ValidateRubricIntegrityHandler implements QueryHandlerInterface
{
    /** @return array{valid: bool, errors: string[]} */
    public function handle(QueryInterface $query): array
    {
        /** @var ValidateRubricIntegrityQuery $query */
        // childrenRecursive (không phải children 1 cấp) — cây có thể sâu hơn 1 cấp
        // (VD chè: 6.1 -> 6.1.1), phải eager-load hết để tránh lazy-load bị chặn.
        $version = OcopRubricVersion::with('sections.criteria.childrenRecursive', 'sections.criteria.options')
            ->findOrFail($query->rubricVersionId);

        $errors = [];

        $sectionTotal = $version->sections->sum('max_score');
        if (bccomp((string) $sectionTotal, (string) $version->total_max_score, 2) !== 0) {
            $errors[] = "Tổng điểm 3 phần ({$sectionTotal}) khác total_max_score ({$version->total_max_score}).";
        }

        foreach ($version->sections as $section) {
            $roots = $section->criteria->whereNull('parent_id');

            $rootsTotal = $roots->sum('max_score');
            if (bccomp((string) $rootsTotal, (string) $section->max_score, 2) !== 0) {
                $errors[] = "Phần {$section->code}: tổng điểm các Mục gốc ({$rootsTotal}) khác max_score của Phần ({$section->max_score}).";
            }

            foreach ($roots as $root) {
                $this->validateSubtree($root, $errors);
            }
        }

        return ['valid' => empty($errors), 'errors' => $errors];
    }

    private function validateSubtree(OcopRubricCriterion $node, array &$errors): void
    {
        if ($node->is_scorable) {
            $highest = $node->options->max('points') ?? 0;
            if (bccomp((string) $highest, (string) $node->max_score, 2) > 0) {
                $errors[] = "Tiêu chí {$node->code} có option {$highest}đ vượt max_score {$node->max_score}đ.";
            }
            return;
        }

        $childrenTotal = $node->childrenRecursive->sum('max_score');
        if (bccomp((string) $childrenTotal, (string) $node->max_score, 2) !== 0) {
            $errors[] = "Mục {$node->code}: tổng điểm con ({$childrenTotal}) khác max_score ({$node->max_score}).";
        }

        foreach ($node->childrenRecursive as $child) {
            $this->validateSubtree($child, $errors);
        }
    }
}
