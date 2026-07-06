<?php

namespace Modules\JobPosting\Enums;

enum RequirementLevel: string
{
    case Required   = 'required';
    case Preferred  = 'preferred';
    case NiceToHave = 'nice_to_have';

    public function label(): string
    {
        return match($this) {
            self::Required   => 'Bắt buộc',
            self::Preferred  => 'Ưu tiên',
            self::NiceToHave => 'Có thì tốt',
        };
    }

    public function badgeClass(): string
    {
        return match($this) {
            self::Required   => 'badge-error',
            self::Preferred  => 'badge-warning',
            self::NiceToHave => 'badge-ghost',
        };
    }
}
