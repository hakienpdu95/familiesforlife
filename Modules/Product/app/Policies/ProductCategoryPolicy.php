<?php

namespace Modules\Product\Policies;

use App\Models\User;
use Modules\Product\Models\ProductCategory;

class ProductCategoryPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('product.view');
    }

    public function view(User $user, ProductCategory $productCategory): bool
    {
        return $user->can('product.view');
    }

    public function create(User $user): bool
    {
        return $user->can('product_category.manage');
    }

    public function update(User $user, ProductCategory $productCategory): bool
    {
        return $user->can('product_category.manage');
    }

    public function delete(User $user, ProductCategory $productCategory): bool
    {
        return $user->can('product_category.manage');
    }
}
