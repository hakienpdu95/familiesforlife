<?php

namespace Modules\Heritage\Enums;

/**
 * spec/Heritage_Technical_Specification.md §3.4 — trạng thái workflow biên tập (nháp/xuất bản),
 * tách riêng khỏi 3 enum "mô tả di tích" (§3.1–§3.3) — cùng bản chất OcopProductStatus.
 */
enum HeritageSiteStatus: string
{
    case Draft = 'draft';     // Đang soạn, chưa hiển thị công khai
    case Published = 'published'; // Đã xuất bản — hiện trên trang tỉnh + trang chi tiết

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Nháp',
            self::Published => 'Đã xuất bản',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Draft => 'badge-ghost',
            self::Published => 'badge-success',
        };
    }
}
