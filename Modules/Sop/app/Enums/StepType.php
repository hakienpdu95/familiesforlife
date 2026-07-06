<?php

namespace Modules\Sop\Enums;

enum StepType: string
{
    case Start        = 'start';
    case End          = 'end';
    case Action       = 'action';
    case Decision     = 'decision';
    case SubSop       = 'sub_sop';
    case Notification = 'notification';
    case Wait         = 'wait';

    public function label(): string
    {
        return match($this) {
            self::Start        => 'Bắt đầu',
            self::End          => 'Kết thúc',
            self::Action       => 'Hành động',
            self::Decision     => 'Quyết định',
            self::SubSop       => 'SOP con',
            self::Notification => 'Thông báo',
            self::Wait         => 'Chờ',
        };
    }

    public function shape(): string
    {
        return match($this) {
            self::Start        => 'oval',
            self::End          => 'oval_double',
            self::Action       => 'rect',
            self::Decision     => 'diamond',
            self::SubSop       => 'rect_double',
            self::Notification => 'parallelogram',
            self::Wait         => 'rounded_rect',
        };
    }
}
