<?php

namespace Modules\Organization\Enums;

enum MemberRole: string
{
    case Owner   = 'owner';
    case Admin   = 'admin';
    case Manager = 'manager';
    case Member  = 'member';

    public function label(): string
    {
        return match ($this) {
            self::Owner   => 'Chủ sở hữu',
            self::Admin   => 'Quản trị viên',
            self::Manager => 'Quản lý',
            self::Member  => 'Thành viên',
        };
    }

    /** Các vai trò có thể được mời (không mời Owner qua invitation). */
    public static function invitable(): array
    {
        return [self::Admin, self::Manager, self::Member];
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
