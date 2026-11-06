<?php

namespace App\Enums;

enum AccountType: string
{
    case Free      = 'free';
    case Platform  = 'platform';
    case Suspended = 'suspended';

    public function label(): string
    {
        return match ($this) {
            self::Free      => 'Tự do',
            self::Platform  => 'Nhân sự nền tảng',
            self::Suspended => 'Bị khóa',
        };
    }

    public function canLogin(): bool
    {
        return $this !== self::Suspended;
    }
}
