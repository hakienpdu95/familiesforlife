<?php

namespace App\Shared\Tenancy;

use App\Shared\Tenancy\TenantContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

/**
 * Global scope automatically applied to all models using BelongsToOrganization.
 * Filters queries to the current tenant's organization_id.
 */
final class OrganizationScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        // super-admin (tài khoản quản trị hệ thống mặc định) xem/thao tác được dữ liệu của
        // MỌI tổ chức — không bị giới hạn theo TenantContext hiện tại. Cùng quy tắc đã áp
        // dụng cho resolveRouteBinding() (BelongsToOrganization trait), giờ áp dụng luôn cho
        // mọi query list/index để nhất quán (trước đây chỉ bypass được khi truy cập 1 bản ghi
        // qua route-model-binding, còn danh sách vẫn bị lọc theo org "system" mặc định).
        if (auth()->check() && auth()->user()->hasRole('super-admin')) {
            return;
        }

        if (TenantContext::isSet()) {
            $builder->where(
                $model->getTable() . '.organization_id',
                TenantContext::getOrganizationId()
            );
            return;
        }

        // Failsafe: nếu context chưa được set, trả về tập rỗng thay vì toàn bộ dữ liệu.
        // Điều này bảo vệ khỏi data leakage nếu middleware bị bỏ qua vì bất kỳ lý do gì.
        // Admin cần bypass dùng ->withoutGlobalScope(OrganizationScope::class) hoặc scopeWithoutTenant().
        $builder->whereRaw('0 = 1');
    }
}
