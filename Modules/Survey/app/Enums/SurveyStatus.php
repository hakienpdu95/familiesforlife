<?php

namespace Modules\Survey\Enums;

enum SurveyStatus: int
{
    case Draft  = 0;
    case Active = 1;
    case Closed = 2;

    public function label(): string
    {
        return match($this) {
            self::Draft  => 'Nháp',
            self::Active => 'Đang mở',
            self::Closed => 'Đã đóng',
        };
    }

    public function badgeClass(): string
    {
        return match($this) {
            self::Draft  => 'badge-ghost',
            self::Active => 'badge-success',
            self::Closed => 'badge-error',
        };
    }
}
