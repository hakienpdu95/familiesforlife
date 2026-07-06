<?php

namespace Modules\Lead\Enums;

enum LeadStatus: int
{
    case Active    = 1;
    case Converted = 2;
    case Archived  = 3;
    case OnHold    = 4;

    public function label(): string
    {
        return match($this) {
            self::Active    => 'Đang theo dõi',
            self::Converted => 'Đã chuyển đổi',
            self::Archived  => 'Lưu trữ',
            self::OnHold    => 'Tạm dừng',
        };
    }

    public function badgeClass(): string
    {
        return match($this) {
            self::Active    => 'badge-teal',
            self::Converted => 'badge-green',
            self::Archived  => 'badge-gray',
            self::OnHold    => 'badge-amber',
        };
    }
}
