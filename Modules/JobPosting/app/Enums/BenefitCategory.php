<?php

namespace Modules\JobPosting\Enums;

enum BenefitCategory: string
{
    case Health    = 'health';
    case Finance   = 'finance';
    case Learning  = 'learning';
    case WorkLife  = 'work_life';
    case Equipment = 'equipment';
    case Other     = 'other';

    public function label(): string
    {
        return match($this) {
            self::Health    => 'Sức khỏe',
            self::Finance   => 'Tài chính',
            self::Learning  => 'Học tập & Phát triển',
            self::WorkLife  => 'Cân bằng cuộc sống',
            self::Equipment => 'Thiết bị',
            self::Other     => 'Khác',
        };
    }
}
