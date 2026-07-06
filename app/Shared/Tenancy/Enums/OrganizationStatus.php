<?php

namespace App\Shared\Tenancy\Enums;

enum OrganizationStatus: string
{
    case Active    = 'active';
    case Suspended = 'suspended';
    case Inactive  = 'inactive';

    public function label(): string
    {
        return match($this) {
            self::Active    => 'Đang hoạt động',
            self::Suspended => 'Tạm khóa',
            self::Inactive  => 'Không hoạt động',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Active    => 'badge-success',
            self::Suspended => 'badge-error',
            self::Inactive  => 'badge-ghost',
        };
    }

    public function isActive(): bool
    {
        return $this === self::Active;
    }
}
