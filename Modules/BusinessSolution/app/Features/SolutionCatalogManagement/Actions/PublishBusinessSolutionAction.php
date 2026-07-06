<?php

namespace Modules\BusinessSolution\Features\SolutionCatalogManagement\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\BusinessSolution\Enums\BusinessSolutionStatus;
use Modules\BusinessSolution\Features\SolutionCatalogManagement\Exceptions\BusinessSolutionNotPublishableException;
use Modules\BusinessSolution\Models\BusinessSolution;

/**
 * Chuyển status → published. Điều kiện tiên quyết (spec §1.6): phải tồn tại ít nhất
 * 1 `blueprints` với status=published thuộc solution này (enforce OR-006 tương đương
 * ở tầng Solution — không cho publish Solution rỗng không có Blueprint nào).
 *
 * Bảng `blueprints` thuộc module BusinessBlueprint (chưa tồn tại ở giai đoạn hiện tại) —
 * dùng Schema::hasTable() để không vỡ khi module đó chưa được cài đặt.
 */
class PublishBusinessSolutionAction
{
    use AsAction;

    public function handle(BusinessSolution $businessSolution): BusinessSolution
    {
        if (! $this->hasPublishedBlueprint($businessSolution)) {
            throw new BusinessSolutionNotPublishableException();
        }

        $businessSolution->update(['status' => BusinessSolutionStatus::Published->value]);

        return $businessSolution->fresh();
    }

    private function hasPublishedBlueprint(BusinessSolution $businessSolution): bool
    {
        if (! Schema::hasTable('blueprints')) {
            return false;
        }

        return DB::table('blueprints')
            ->where('business_solution_id', $businessSolution->id)
            ->where('status', 'published')
            ->exists();
    }
}
