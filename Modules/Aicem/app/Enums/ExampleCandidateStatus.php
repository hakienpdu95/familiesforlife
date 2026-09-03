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
            self::Pending  => 'Chờ duyệt',
            self::Approved => 'Đã duyệt',
            self::Rejected => 'Đã từ chối',
        };
    }
}
