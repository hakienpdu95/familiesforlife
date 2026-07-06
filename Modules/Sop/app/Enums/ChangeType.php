<?php

namespace Modules\Sop\Enums;

enum ChangeType: string
{
    case Added     = 'added';
    case Modified  = 'modified';
    case Deleted   = 'deleted';
    case Unchanged = 'unchanged';

    public function label(): string
    {
        return match($this) {
            self::Added     => 'Thêm mới',
            self::Modified  => 'Đã sửa',
            self::Deleted   => 'Đã xóa',
            self::Unchanged => 'Không đổi',
        };
    }

    public function diffColor(): string
    {
        return match($this) {
            self::Added     => '#639922',
            self::Modified  => '#EF9F27',
            self::Deleted   => '#E24B4A',
            self::Unchanged => '#B4B2A9',
        };
    }
}
