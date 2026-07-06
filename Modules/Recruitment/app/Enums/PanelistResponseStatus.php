<?php

namespace Modules\Recruitment\Enums;

enum PanelistResponseStatus: string
{
    case Pending  = 'pending';
    case Accepted = 'accepted';
    case Declined = 'declined';

    public function label(): string
    {
        return match($this) {
            self::Pending  => 'Chờ phản hồi',
            self::Accepted => 'Đã chấp nhận',
            self::Declined => 'Đã từ chối',
        };
    }
}
