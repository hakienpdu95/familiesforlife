<?php

namespace Modules\Project\Enums;

enum ProjectStatus: string
{
    case Planning   = 'planning';
    case Active     = 'active';
    case OnHold     = 'on_hold';
    case Completed  = 'completed';
    case Cancelled  = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Planning  => 'Lên kế hoạch',
            self::Active    => 'Đang thực hiện',
            self::OnHold    => 'Tạm dừng',
            self::Completed => 'Hoàn thành',
            self::Cancelled => 'Đã hủy',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Planning  => 'badge-info',
            self::Active    => 'badge-success',
            self::OnHold    => 'badge-warning',
            self::Completed => 'badge-primary',
            self::Cancelled => 'badge-ghost',
        };
    }
}
