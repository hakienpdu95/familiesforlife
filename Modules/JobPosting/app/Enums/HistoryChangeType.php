<?php

namespace Modules\JobPosting\Enums;

enum HistoryChangeType: string
{
    case Created       = 'created';
    case Updated       = 'updated';
    case StatusChanged = 'status_changed';
    case Published     = 'published';
    case Closed        = 'closed';
    case Archived      = 'archived';

    public function label(): string
    {
        return match($this) {
            self::Created       => 'Tạo mới',
            self::Updated       => 'Cập nhật',
            self::StatusChanged => 'Thay đổi trạng thái',
            self::Published     => 'Publish',
            self::Closed        => 'Đóng tuyển',
            self::Archived      => 'Lưu trữ',
        };
    }
}
