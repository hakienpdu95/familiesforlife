<?php

namespace Modules\Marketplace\Enums;

enum ListingVisibility: string
{
    case PUBLIC       = 'public';
    case UNLISTED     = 'unlisted';
    case MEMBERS_ONLY = 'members_only';

    public function label(): string
    {
        return match ($this) {
            self::PUBLIC       => 'Công khai',
            self::UNLISTED     => 'Chỉ qua link',
            self::MEMBERS_ONLY => 'Thành viên đã đăng nhập',
        };
    }
}
