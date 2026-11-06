<?php

namespace Database\Seeders\Concerns;

use App\Enums\RoleEnum;
use App\Models\User;

trait ResolvesPlatformAccounts
{
    /**
     * @param  array<string, RoleEnum>  $roles
     * @return array<string, User>|null
     */
    protected function platformAccounts(array $roles): ?array
    {
        $accounts = [];
        $missing = [];

        foreach ($roles as $key => $role) {
            $user = User::withGlobalRole([$role->value])->firstWhere('is_active', true);

            $user ? $accounts[$key] = $user : $missing[] = $role->value;
        }

        if ($missing !== []) {
            $this->command?->warn('  ⚠ Thiếu tài khoản Platform cho vai trò: '.implode(', ', $missing)
                .' — chạy Modules\\Approval\\Database\\Seeders\\ApprovalDatabaseSeeder trước.');

            return null;
        }

        return $accounts;
    }
}
