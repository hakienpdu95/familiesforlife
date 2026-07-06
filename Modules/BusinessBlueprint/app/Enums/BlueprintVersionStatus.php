<?php

namespace Modules\BusinessBlueprint\Enums;

enum BlueprintVersionStatus: string
{
    case Draft           = 'draft';
    case InDesign        = 'in_design';
    case ReadyForReview  = 'ready_for_review';
    case Reviewing       = 'reviewing';
    case Approved        = 'approved';
    case Published       = 'published';
    case Deprecated      = 'deprecated';
    case Archived        = 'archived';

    public function label(): string
    {
        return match ($this) {
            self::Draft          => 'Nháp',
            self::InDesign        => 'Đang thiết kế',
            self::ReadyForReview  => 'Sẵn sàng review',
            self::Reviewing       => 'Đang review',
            self::Approved        => 'Đã duyệt',
            self::Published       => 'Đã phát hành',
            self::Deprecated      => 'Đã lỗi thời',
            self::Archived        => 'Đã lưu trữ',
        };
    }

    /** Version ở các trạng thái này KHÔNG ĐƯỢC sửa nội dung — chỉ Clone. */
    public function isImmutable(): bool
    {
        return in_array($this, [self::Published, self::Deprecated, self::Archived], true);
    }

    /** Chỉ trạng thái Published mới được Deploy (BR-003 A04.1, OR-006 A07). */
    public function isDeployable(): bool
    {
        return $this === self::Published;
    }
}
