<?php

namespace Modules\Post\Enums;

enum VersionTrigger: string
{
    case Save    = 'save';     // UpdateTranslationAction — mỗi lần bấm "Cập nhật bài viết"
    case Publish = 'publish';  // PublishArticleAction / PublishAllTranslationsAction — chốt "bản đã lên sóng"
    case Restore = 'restore';  // RestoreArticleVersionAction — khôi phục 1 bản cũ

    public function label(): string
    {
        return match ($this) {
            self::Save    => 'Lưu chỉnh sửa',
            self::Publish => 'Xuất bản',
            self::Restore => 'Khôi phục từ phiên bản cũ',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Save    => 'badge-ghost',
            self::Publish => 'badge-success',
            self::Restore => 'badge-warning',
        };
    }

    /** Version thuộc trigger này KHÔNG bao giờ bị auto-prune xoá (§10) — mốc tuân thủ/audit. */
    public function isProtectedFromPruning(): bool
    {
        return $this !== self::Save;
    }
}
