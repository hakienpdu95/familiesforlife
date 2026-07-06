<?php

namespace App\Enums;

enum AccountType: string
{
    case Free      = 'free';
    case OrgMember = 'org_member';
    case Suspended = 'suspended';

    public function label(): string
    {
        return match ($this) {
            self::Free      => 'Tự do',
            self::OrgMember => 'Thành viên tổ chức',
            self::Suspended => 'Bị khóa',
        };
    }

    public function canLogin(): bool
    {
        return $this !== self::Suspended;
    }

    public function canAccessOrgWorkspace(): bool
    {
        return $this === self::OrgMember;
    }
}
