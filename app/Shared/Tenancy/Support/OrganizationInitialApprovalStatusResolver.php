<?php

namespace App\Shared\Tenancy\Support;

use App\Shared\Tenancy\Enums\OrganizationStatus;
use Illuminate\Database\Eloquent\Model;
use Modules\Approval\Contracts\ResolvesInitialApprovalStatus;
use Modules\Approval\Enums\ApprovalStatus;

/**
 * Dùng bởi `php artisan approval:backfill-subjects organization` — tổ chức đã tồn tại và đang
 * hoạt động bình thường (Active) trước khi tích hợp Platform Approval Gateway được coi là đã
 * Published, tránh khoá tài khoản đang dùng thật. Suspended/Inactive coi là Draft (chưa từng
 * qua kiểm duyệt tập trung).
 */
class OrganizationInitialApprovalStatusResolver implements ResolvesInitialApprovalStatus
{
    public static function resolve(Model $entity): ApprovalStatus
    {
        /** @var \App\Shared\Tenancy\Models\Organization $entity */
        return $entity->status === OrganizationStatus::Active
            ? ApprovalStatus::Published
            : ApprovalStatus::Draft;
    }
}
