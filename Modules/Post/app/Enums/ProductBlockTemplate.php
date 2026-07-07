<?php

namespace Modules\Post\Enums;

enum ProductBlockTemplate: string
{
    case SingleCard  = 'single_card';   // 1 sản phẩm, ảnh lớn bên trái + nội dung bên phải
    case MultiGrid   = 'multi_grid';    // lưới 2-4 sản phẩm, mỗi ô 1 ảnh/tên/giá/nút
    case Banner      = 'banner';        // dải ngang full-width, nhấn mạnh 1 sản phẩm chủ đạo
    case CompactList = 'compact_list';  // danh sách gọn (ảnh nhỏ + tên + 1 nút), nhiều sản phẩm liên tiếp

    public function label(): string
    {
        return match ($this) {
            self::SingleCard  => 'Thẻ đơn',
            self::MultiGrid   => 'Lưới nhiều sản phẩm',
            self::Banner      => 'Banner nổi bật',
            self::CompactList => 'Danh sách gọn',
        };
    }

    /** single_card/banner = đúng 1 item; multi_grid/compact_list = 2-7 item (docs/post-module-spec.md §7.6/§9.8.2). */
    public function minItems(): int
    {
        return match ($this) {
            self::SingleCard, self::Banner => 1,
            self::MultiGrid, self::CompactList => 2,
        };
    }

    public function maxItems(): int
    {
        return match ($this) {
            self::SingleCard, self::Banner => 1,
            self::MultiGrid, self::CompactList => 7,
        };
    }
}
