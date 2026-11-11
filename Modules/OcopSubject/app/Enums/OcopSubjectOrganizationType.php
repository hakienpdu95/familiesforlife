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
            self::Enterprise => __('Doanh nghiệp'),
            self::Cooperative => __('Hợp tác xã'),
            self::Household => __('Hộ kinh doanh'),
        };
    }

    public function requiresOcopDecision(): bool
    {
        return $this === self::Household;
    }
}
