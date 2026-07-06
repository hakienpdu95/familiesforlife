<?php

namespace Modules\Marketplace\Enums;

enum ApplicantAccountType: string
{
    case Individual = 'individual';
    case Team       = 'team';
    case Agency     = 'agency';

    public function label(): string
    {
        return match ($this) {
            self::Individual => 'Cá nhân',
            self::Team       => 'Nhóm',
            self::Agency     => 'Agency',
        };
    }
}
