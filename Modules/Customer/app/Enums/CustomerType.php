<?php
namespace Modules\Customer\Enums;

enum CustomerType: int
{
    case Individual = 1;
    case Business   = 2;

    public function label(): string
    {
        return match($this) {
            self::Individual => 'Cá nhân',
            self::Business   => 'Doanh nghiệp',
        };
    }

    public function badgeClass(): string
    {
        return match($this) {
            self::Individual => 'badge-info',
            self::Business   => 'badge-primary',
        };
    }
}
