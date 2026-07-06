<?php

namespace Modules\Recruitment\Enums;

enum PanelistRole: string
{
    case Interviewer = 'interviewer';
    case Observer    = 'observer';
    case NoteTaker   = 'note_taker';

    public function label(): string
    {
        return match($this) {
            self::Interviewer => 'Người phỏng vấn',
            self::Observer    => 'Quan sát viên',
            self::NoteTaker   => 'Người ghi chép',
        };
    }
}
