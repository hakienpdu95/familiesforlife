<?php

namespace Modules\Ocop\Policies;

use App\Models\User;
use Modules\Ocop\Models\OcopCategory;

/**
 * spec/danhmuc.html — danh mục OCOP đã chuẩn hóa theo bảng phân loại chính thức, chỉ còn xem
 * (không create/update/delete) — cùng permission ocop.manage với OcopProductPolicy.
 */
class OcopCategoryPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('ocop.manage');
    }

    public function view(User $user, OcopCategory $category): bool
    {
        return $user->can('ocop.manage');
    }
}
