<?php

namespace Modules\Department\Enums;

enum DepartmentStatus: string
{
    case Active   = 'active';
    case Inactive = 'inactive';
    case Merged   = 'merged';

    public function label(): string
    {
        return match ($this) {
            self::Active   => 'Hoạt động',
            self::Inactive => 'Tạm dừng',
            self::Merged   => 'Đã sáp nhập',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Active   => 'badge-success',
            self::Inactive => 'badge-warning',
            self::Merged   => 'badge-ghost',
        };
    }
}
