<?php

namespace Modules\WorkflowAutomation\Enums;

enum StepStatus: int
{
    case Success   = 1;
    case Skipped   = 2;
    case Failed    = 3;
    case Scheduled = 4;
    case Halted    = 5;
    case Waiting   = 6;

    public function label(): string
    {
        return match($this) {
            self::Success   => 'Thành công',
            self::Skipped   => 'Bỏ qua',
            self::Failed    => 'Lỗi',
            self::Scheduled => 'Đã lên lịch',
            self::Halted    => 'Bị dừng',
            self::Waiting   => 'Chờ phê duyệt',
        };
    }
}
