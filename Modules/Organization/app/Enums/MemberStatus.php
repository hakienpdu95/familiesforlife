<?php

namespace Modules\Organization\Enums;

enum MemberStatus: string
{
    case Active    = 'active';
    case Inactive  = 'inactive';
    case Paused    = 'paused';
    case Suspended = 'suspended';

    public function label(): string
    {
        return match ($this) {
            self::Active    => 'Đang hoạt động',
            self::Inactive  => 'Đã nghỉ',
            self::Paused    => 'Tạm dừng',
            self::Suspended => 'Bị khóa (chờ xác nhận)',
        };
    }

    public function isActiveInOrg(): bool
    {
        return $this === self::Active || $this === self::Paused;
    }
}
