<?php

namespace Modules\Marketplace\Enums;

enum ApplicationStatus: string
{
    case Submitted   = 'submitted';
    case Viewed      = 'viewed';
    case Shortlisted = 'shortlisted';
    case Rejected    = 'rejected';
    case Hired       = 'hired';
    case Withdrawn   = 'withdrawn';

    public function label(): string
    {
        return match ($this) {
            self::Submitted   => 'Đã nộp',
            self::Viewed      => 'Đã xem',
            self::Shortlisted => 'Shortlist',
            self::Rejected    => 'Từ chối',
            self::Hired       => 'Đã tuyển',
            self::Withdrawn   => 'Đã rút',
        };
    }
}
