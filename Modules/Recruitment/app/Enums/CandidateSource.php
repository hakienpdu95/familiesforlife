<?php

namespace Modules\Recruitment\Enums;

enum CandidateSource: string
{
    case Direct      = 'direct';
    case Linkedin    = 'linkedin';
    case Referral    = 'referral';
    case JobBoard    = 'job_board';
    case Agency      = 'agency';
    case Marketplace = 'marketplace';
    case CareerPage  = 'career_page';
    case Other       = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Direct      => 'Trực tiếp',
            self::Linkedin    => 'LinkedIn',
            self::Referral    => 'Giới thiệu',
            self::JobBoard    => 'Job Board',
            self::Agency      => 'Agency',
            self::Marketplace => 'Marketplace',
            self::CareerPage  => 'Career Page',
            self::Other       => 'Khác',
        };
    }
}
