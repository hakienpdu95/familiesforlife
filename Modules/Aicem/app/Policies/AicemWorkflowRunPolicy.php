<?php

namespace Modules\Aicem\Policies;

use App\Models\User;

/**
 * Không gắn với 1 Eloquent model cụ thể (chạy workflow/quyết định suggestion không phải thao
 * tác CRUD trên 1 resource) — đăng ký qua Gate::define() trong AicemServiceProvider thay vì
 * Gate::policy(). Kiểm permission aicem.use (spec/AICEM_Technical_Specification.md mục 10/12).
 */
class AicemWorkflowRunPolicy
{
    public function run(User $user): bool
    {
        return $user->can('aicem.use');
    }

    public function decide(User $user): bool
    {
        return $user->can('aicem.use');
    }
}
