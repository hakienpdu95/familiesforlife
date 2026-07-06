<?php
namespace Modules\Customer\Enums;

enum CustomerLifecycleStage: int
{
    case Prospect = 1;
    case Active   = 2;
    case VIP      = 3;
    case Inactive = 4;
    case Churned  = 5;

    public function label(): string
    {
        return match($this) {
            self::Prospect => 'Tiềm năng',
            self::Active   => 'Đang hoạt động',
            self::VIP      => 'VIP',
            self::Inactive => 'Không hoạt động',
            self::Churned  => 'Đã rời bỏ',
        };
    }

    public function badgeClass(): string
    {
        return match($this) {
            self::Prospect => 'badge-ghost',
            self::Active   => 'badge-success',
            self::VIP      => 'badge-warning',
            self::Inactive => 'badge-neutral',
            self::Churned  => 'badge-error',
        };
    }
}
