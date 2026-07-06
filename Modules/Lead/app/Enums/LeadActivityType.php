<?php

namespace Modules\Lead\Enums;

enum LeadActivityType: int
{
    case Call        = 1;
    case Email       = 2;
    case Meeting     = 3;
    case Note        = 4;
    case StageChange = 5;
    case Assign      = 6;
    case ScoreUpdate = 7;
    case System      = 8;
    case Task        = 9;
    case Visit       = 10;

    public function label(): string
    {
        return match($this) {
            self::Call        => 'Cuộc gọi',
            self::Email       => 'Email',
            self::Meeting     => 'Cuộc họp',
            self::Note        => 'Ghi chú',
            self::StageChange => 'Đổi tình trạng',
            self::Assign      => 'Phân công',
            self::ScoreUpdate => 'Cập nhật điểm',
            self::System      => 'Hệ thống',
            self::Task        => 'Công việc',
            self::Visit       => 'Thăm khách hàng',
        };
    }

    public function icon(): string
    {
        return match($this) {
            self::Call        => 'ti-phone',
            self::Email       => 'ti-mail',
            self::Meeting     => 'ti-calendar-event',
            self::Note        => 'ti-file-text',
            self::StageChange => 'ti-arrows-right-left',
            self::Assign      => 'ti-user-check',
            self::ScoreUpdate => 'ti-target-arrow',
            self::System      => 'ti-settings',
            self::Task        => 'ti-checkbox',
            self::Visit       => 'ti-map-pin',
        };
    }
}
