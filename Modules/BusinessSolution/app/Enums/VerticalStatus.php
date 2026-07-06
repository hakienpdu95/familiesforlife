<?php

namespace Modules\BusinessSolution\Enums;

enum VerticalStatus: string
{
    case Active   = 'active';
    case Inactive = 'inactive';

    public function label(): string
    {
        return match ($this) {
            self::Active   => 'Đang hoạt động',
            self::Inactive => 'Ngừng hoạt động',
        };
    }
}
