<?php

namespace Modules\ActivityLog\Enums;

enum LogLevel: int
{
    case Debug    = 1;
    case Info     = 2;
    case Warning  = 3;
    case Error    = 4;
    case Critical = 5;

    public function label(): string
    {
        return match($this) {
            self::Debug    => 'Debug',
            self::Info     => 'Info',
            self::Warning  => 'Cảnh báo',
            self::Error    => 'Lỗi',
            self::Critical => 'Nghiêm trọng',
        };
    }

    public function badgeClass(): string
    {
        return match($this) {
            self::Debug    => 'badge-gray',
            self::Info     => 'badge-teal',
            self::Warning  => 'badge-amber',
            self::Error    => 'badge-red',
            self::Critical => 'badge-crimson',
        };
    }
}
