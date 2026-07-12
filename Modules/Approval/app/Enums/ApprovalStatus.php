<?php

namespace Modules\Approval\Enums;

enum ApprovalStatus: string
{
    case Draft     = 'draft';
    case Pending   = 'pending';
    case Approved  = 'approved';
    case Published = 'published';
    case Archived  = 'archived';

    public function label(): string
    {
        return match ($this) {
            self::Draft     => 'Nháp',
            self::Pending   => 'Chờ duyệt',
            self::Approved  => 'Đã duyệt',
            self::Published => 'Đã xuất bản',
            self::Archived  => 'Lưu trữ',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Draft     => 'badge-ghost',
            self::Pending   => 'badge-warning',
            self::Approved  => 'badge-info',
            self::Published => 'badge-success',
            self::Archived  => 'badge-neutral',
        };
    }

    /**
     * Transition hợp lệ — validate ở tầng Action, KHÔNG chỉ ở UI (theo TranslationStatus của
     * Modules/Post). `Approved → Pending` và `Published → Pending` KHÔNG phải do người dùng
     * bấm nút — đây là transition tự động khi nội dung bị sửa sau khi đã duyệt/đã lên cổng
     * thông tin (spec §7.1, §8.4): nội dung đã qua duyệt/đã live mà bị đổi thì phải chờ duyệt
     * lại. Draft/Pending sửa nội dung thì không cần transition gì.
     */
    public function canTransitionTo(self $target): bool
    {
        return match ($this) {
            self::Draft     => $target === self::Pending,
            self::Pending   => in_array($target, [self::Approved, self::Draft], true), // reject → Draft
            self::Approved  => in_array($target, [self::Published, self::Pending], true),
            self::Published => in_array($target, [self::Archived, self::Pending], true),
            self::Archived  => false,
        };
    }
}
