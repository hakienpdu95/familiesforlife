<?php

namespace Modules\Recruitment\Enums;

enum StageLogResult: string
{
    case Passed    = 'passed';
    case Failed    = 'failed';
    case Skipped   = 'skipped';
    case MovedBack = 'moved_back';

    public function label(): string
    {
        return match ($this) {
            self::Passed    => 'Đạt',
            self::Failed    => 'Không đạt',
            self::Skipped   => 'Bỏ qua',
            self::MovedBack => 'Trả về',
        };
    }
}
