<?php
namespace Modules\Marketplace\Enums;

enum ReviewRelationType: string
{
    case Hired               = 'hired';
    case ProjectCompleted    = 'project_completed';
    case Collaboration       = 'collaboration';

    public function label(): string
    {
        return match ($this) {
            self::Hired            => 'Đã tuyển dụng',
            self::ProjectCompleted => 'Dự án hoàn thành',
            self::Collaboration    => 'Hợp tác',
        };
    }
}
