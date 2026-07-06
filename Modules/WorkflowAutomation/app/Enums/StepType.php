<?php

namespace Modules\WorkflowAutomation\Enums;

enum StepType: int
{
    case Automated = 1;
    case UserTask  = 2;
    case Control   = 3;

    public function label(): string
    {
        return match($this) {
            self::Automated => 'Tự động',
            self::UserTask  => 'Nhiệm vụ người dùng',
            self::Control   => 'Kiểm soát luồng',
        };
    }
}
