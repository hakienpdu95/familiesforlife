<?php

namespace Modules\Sop\Enums;

enum ApprovalAction: string
{
    case Approved  = 'approved';
    case Rejected  = 'rejected';
    case Forwarded = 'forwarded';

    public function label(): string
    {
        return match($this) {
            self::Approved  => 'Đã duyệt',
            self::Rejected  => 'Từ chối',
            self::Forwarded => 'Chuyển tiếp',
        };
    }
}
