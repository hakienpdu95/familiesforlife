<?php

namespace Modules\Event\Enums;

/**
 * spec/Event_Management_Technical_Specification.md §5.4/§6 — state machine riêng cho Event,
 * KHÔNG dùng chung Modules\Approval\Enums\ApprovalStatus (lý do: §3.2 — ApprovalSubject bắt
 * buộc organization_id NOT NULL, Event là tài sản nền tảng như Post, không tổ chức nào sở hữu).
 */
enum EventStatus: string
{
    case Submitted = 'submitted'; // vừa nộp qua form public, chờ platform_content_editor sơ duyệt
    case Approved  = 'approved';  // editor duyệt xong, chờ platform_content_head xuất bản
    case Published = 'published'; // hiển thị công khai
    case Rejected  = 'rejected';  // terminal — độc giả không có tài khoản để sửa & nộp lại
    case Expired   = 'expired';   // tự động chuyển bởi ExpirePastEventsJob khi end_date đã qua (Phase 2/§11.1)
    case Archived  = 'archived';  // staff gỡ thủ công (spam phát hiện sau publish, trùng lặp, vi phạm chính sách)

    public function label(): string
    {
        return match ($this) {
            self::Submitted => 'Chờ duyệt',
            self::Approved  => 'Đã duyệt',
            self::Published => 'Đã xuất bản',
            self::Rejected  => 'Đã từ chối',
            self::Expired   => 'Đã kết thúc',
            self::Archived  => 'Lưu trữ',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Submitted => 'badge-warning',
            self::Approved  => 'badge-info',
            self::Published => 'badge-success',
            self::Rejected  => 'badge-error',
            self::Expired   => 'badge-ghost',
            self::Archived  => 'badge-neutral',
        };
    }

    /** Validate ở tầng Action (`abort_unless`), không chỉ ở UI — cùng nguyên tắc TranslationStatus/ApprovalStatus. */
    public function canTransitionTo(self $target): bool
    {
        return match ($this) {
            self::Submitted => in_array($target, [self::Approved, self::Rejected], true),
            self::Approved  => in_array($target, [self::Published, self::Rejected, self::Archived], true),
            self::Published => in_array($target, [self::Expired, self::Archived], true),
            self::Expired   => $target === self::Archived,
            self::Rejected, self::Archived => false,
        };
    }
}
