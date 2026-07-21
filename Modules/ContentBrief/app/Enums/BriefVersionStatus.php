<?php

namespace Modules\ContentBrief\Enums;

/**
 * spec/ContentBrief_Technical_Specification.md §3.1/§0 — state machine đơn giản 5 trạng thái,
 * không tái sử dụng Modules/WorkflowAutomation (brief không cần lịch xuất bản như bài viết).
 */
enum BriefVersionStatus: string
{
    case Draft    = 'draft';
    case InReview = 'in_review';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Archived = 'archived';

    public function label(): string
    {
        return match ($this) {
            self::Draft    => 'Nháp',
            self::InReview => 'Đang chờ duyệt',
            self::Approved => 'Đã duyệt',
            self::Rejected => 'Bị từ chối',
            self::Archived => 'Lưu trữ',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Draft    => 'badge-ghost',
            self::InReview => 'badge-warning',
            self::Approved => 'badge-success',
            self::Rejected => 'badge-error',
            self::Archived => 'badge-neutral',
        };
    }
}
