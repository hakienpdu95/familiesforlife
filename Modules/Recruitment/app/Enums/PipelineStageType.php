<?php

namespace Modules\Recruitment\Enums;

enum PipelineStageType: string
{
    case Screening  = 'screening';
    case Assessment = 'assessment';
    case Interview  = 'interview';
    case Offer      = 'offer';
    case Hired      = 'hired';
    case Rejected   = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::Screening  => 'Sàng lọc hồ sơ',
            self::Assessment => 'Bài kiểm tra',
            self::Interview  => 'Phỏng vấn',
            self::Offer      => 'Offer',
            self::Hired      => 'Đã nhận việc',
            self::Rejected   => 'Từ chối',
        };
    }

    public function isTerminal(): bool
    {
        return match ($this) {
            self::Hired, self::Rejected => true,
            default                     => false,
        };
    }
}
