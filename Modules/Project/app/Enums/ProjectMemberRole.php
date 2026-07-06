<?php

namespace Modules\Project\Enums;

enum ProjectMemberRole: string
{
    case Lead        = 'lead';
    case Member      = 'member';
    case Advisor     = 'advisor';
    case Stakeholder = 'stakeholder';

    public function label(): string
    {
        return match ($this) {
            self::Lead        => 'Trưởng nhóm',
            self::Member      => 'Thành viên',
            self::Advisor     => 'Cố vấn',
            self::Stakeholder => 'Bên liên quan',
        };
    }
}
