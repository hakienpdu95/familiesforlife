<?php

namespace Modules\Assessment\Enums;

enum VerificationMethod: string
{
    case Email      = 'email';
    case VneId      = 'vne_id';
    case Passport   = 'passport';

    public function label(): string
    {
        return match ($this) {
            self::Email    => 'Email',
            self::VneId    => 'VNeID',
            self::Passport => 'Hộ chiếu',
        };
    }

    public function trustLevelGranted(): int
    {
        return match ($this) {
            self::Email              => 1,
            self::VneId,
            self::Passport           => 4,
        };
    }
}
