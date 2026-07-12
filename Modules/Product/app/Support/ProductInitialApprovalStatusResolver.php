<?php

namespace Modules\Product\Support;

use Illuminate\Database\Eloquent\Model;
use Modules\Approval\Contracts\ResolvesInitialApprovalStatus;
use Modules\Approval\Enums\ApprovalStatus;
use Modules\Product\Enums\ProductStatus;
use Modules\Product\Models\Product;

/**
 * spec/Workflow_Approval_Technical_Specification.md §9.6 — dùng bởi
 * `php artisan approval:backfill-subjects product` để sản phẩm đang bán bình thường
 * (Active/OutOfStock/Discontinued) không bị coi là "chưa duyệt" sau khi bật tính năng — tránh
 * gãy catalog hiện có. Chỉ Inactive (tạm ẩn) mới coi là Draft (chưa từng công khai).
 */
class ProductInitialApprovalStatusResolver implements ResolvesInitialApprovalStatus
{
    public static function resolve(Model $entity): ApprovalStatus
    {
        /** @var Product $entity */
        return match ($entity->status) {
            ProductStatus::Active, ProductStatus::OutOfStock, ProductStatus::Discontinued => ApprovalStatus::Published,
            ProductStatus::Inactive => ApprovalStatus::Draft,
        };
    }
}
