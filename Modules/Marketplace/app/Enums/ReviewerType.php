<?php
namespace Modules\Marketplace\Enums;

enum ReviewerType: string
{
    case Org       = 'org';
    case Applicant = 'applicant';

    public function label(): string
    {
        return match ($this) {
            self::Org       => 'Doanh nghiệp',
            self::Applicant => 'Ứng viên',
        };
    }
}
