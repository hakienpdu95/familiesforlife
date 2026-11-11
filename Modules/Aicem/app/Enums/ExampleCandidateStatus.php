<?php

namespace Modules\Aicem\Enums;

enum ExampleCandidateStatus: string
{
    case Pending  = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::Pending  => __('Chờ duyệt'),
            self::Approved => __('Đã duyệt'),
            self::Rejected => __('Đã từ chối'),
        };
    }
}
