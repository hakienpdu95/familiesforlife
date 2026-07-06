<?php

namespace Modules\Survey\Enums;

enum ResponseStatus: int
{
    case Partial  = 0;
    case Complete = 1;

    public function label(): string
    {
        return match ($this) {
            self::Partial  => 'Đang điền',
            self::Complete => 'Hoàn chỉnh',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Partial  => 'badge-warning',
            self::Complete => 'badge-success',
        };
    }
}
