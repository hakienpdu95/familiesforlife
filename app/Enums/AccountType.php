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
            self::Free      => __('Tự do'),
            self::Platform  => __('Nhân sự nền tảng'),
            self::Suspended => __('Bị khóa'),
        };
    }

    public function canLogin(): bool
    {
        return $this !== self::Suspended;
    }
}
