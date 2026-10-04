<?php

namespace Modules\OcopSubject\Enums;

enum OcopSubjectOrganizationType: string
{
    case Enterprise = 'enterprise';
    case Cooperative = 'cooperative';
    case Household = 'household';

    public function label(): string
    {
        return match ($this) {
            self::Enterprise => 'Doanh nghiệp',
            self::Cooperative => 'Hợp tác xã',
            self::Household => 'Hộ kinh doanh',
        };
    }

    public function requiresOcopDecision(): bool
    {
        return $this === self::Household;
    }
}
