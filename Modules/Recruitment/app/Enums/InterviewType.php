<?php

namespace Modules\Recruitment\Enums;

enum InterviewType: string
{
    case PhoneScreen = 'phone_screen';
    case Video       = 'video';
    case Onsite      = 'onsite';
    case Technical   = 'technical';
    case CaseStudy   = 'case_study';
    case Panel       = 'panel';

    public function label(): string
    {
        return match($this) {
            self::PhoneScreen => 'Phỏng vấn qua điện thoại',
            self::Video       => 'Phỏng vấn video',
            self::Onsite      => 'Phỏng vấn trực tiếp',
            self::Technical   => 'Phỏng vấn kỹ thuật',
            self::CaseStudy   => 'Case study',
            self::Panel       => 'Panel interview',
        };
    }
}
