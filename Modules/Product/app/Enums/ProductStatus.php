<?php

namespace Modules\Product\Enums;

enum ProductStatus: string
{
    case Active       = 'active';
    case Inactive     = 'inactive';       // tạm ẩn khỏi picker, chưa xoá
    case Discontinued = 'discontinued';   // ngừng kinh doanh vĩnh viễn, vẫn hiển thị được trong box cũ (đọc-only)
    case OutOfStock   = 'out_of_stock';   // vẫn hiển thị, ẩn nút "Mua ngay" mặc định

    public function label(): string
    {
        return match ($this) {
            self::Active       => 'Đang hoạt động',
            self::Inactive     => 'Tạm ẩn',
            self::Discontinued => 'Ngừng kinh doanh',
            self::OutOfStock   => 'Hết hàng',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Active       => 'badge-success',
            self::Inactive     => 'badge-ghost',
            self::Discontinued => 'badge-error',
            self::OutOfStock   => 'badge-warning',
        };
    }
}
