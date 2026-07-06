<?php

namespace Modules\Branch\Enums;

enum BranchStatus: string
{
    case Active   = 'active';
    case Inactive = 'inactive';
    case Closed   = 'closed';

    public function label(): string
    {
        return match ($this) {
            self::Active   => 'Hoạt động',
            self::Inactive => 'Tạm dừng',
            self::Closed   => 'Đã đóng',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Active   => 'badge-success',
            self::Inactive => 'badge-warning',
            self::Closed   => 'badge-error',
        };
    }
}
