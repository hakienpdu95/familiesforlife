<?php

namespace Modules\OrganizationSolution\Features\SolutionActivation\Actions\Concerns;

use Modules\OrganizationSolution\Enums\OrganizationSolutionStatus;
use Modules\OrganizationSolution\Models\OrganizationSolution;

/**
 * Mọi Configure*Action của wizard (Bước 3-7) tự động đẩy status draft → configuring
 * ngay khi tổ chức bắt đầu cấu hình bước đầu tiên (spec §3.6 không có action riêng
 * cho chuyển tiếp này, nhưng §3.5/§3.6 giả định status đã là "configuring" trong
 * suốt wizard trước khi MarkSolutionReadyAction chuyển configuring → ready).
 */
trait AdvancesToConfiguring
{
    private function advanceToConfiguring(OrganizationSolution $orgSolution): void
    {
        if ($orgSolution->status === OrganizationSolutionStatus::Draft->value) {
            $orgSolution->update(['status' => OrganizationSolutionStatus::Configuring->value]);
        }
    }
}
