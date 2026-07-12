<?php

namespace Modules\Approval\Contracts;

use Illuminate\Database\Eloquent\Model;
use Modules\Approval\Enums\ApprovalStatus;

/**
 * Module tiêu thụ implement interface này (FQCN class-string khai báo trong
 * config('approval.subjects.{type}.initial_status_resolver')) để tự map trạng thái cũ của
 * entity sang ApprovalStatus ban đầu khi backfill (vd Product: ProductStatus::Active/
 * OutOfStock/Discontinued → Published, Inactive → Draft). Optional — nếu không khai báo, mọi
 * bản ghi backfill mặc định Draft (an toàn nhất: coi như chưa từng công khai).
 */
interface ResolvesInitialApprovalStatus
{
    public static function resolve(Model $entity): ApprovalStatus;
}
