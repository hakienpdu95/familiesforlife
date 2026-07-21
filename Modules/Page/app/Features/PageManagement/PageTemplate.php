<?php

namespace Modules\Page\Features\PageManagement;

/**
 * spec/Page_Static_Pages_Technical_Specification.md §3.2.1 — registry template do developer
 * khai báo trong code, KHÔNG phải bảng DB. Cột `pages.template` chỉ lưu 1 khoá chuỗi; Admin
 * chỉ chọn từ dropdown (options()), không tự nhập chuỗi — tránh trỏ tới 1 view không tồn tại.
 *
 * Thêm 1 template thiết kế riêng mới: thêm 1 dòng vào MAP + tạo view Blade tương ứng dưới
 * resources/views/public/templates/ — KHÔNG cần sửa Model/Controller/route.
 */
final class PageTemplate
{
    private const MAP = [
        'default' => ['label' => 'Mặc định (nội dung WYSIWYG)', 'view' => 'page::public.show'],
        'about'   => ['label' => 'Giới thiệu (thiết kế riêng)',  'view' => 'page::public.templates.about'],
        'contact' => ['label' => 'Liên hệ (thiết kế riêng)',     'view' => 'page::public.templates.contact'],
    ];

    public static function viewFor(string $key): string
    {
        return self::MAP[$key]['view'] ?? self::MAP['default']['view'];
    }

    /** @return array<string, string> key => label, dùng đổ vào <select> form admin. */
    public static function options(): array
    {
        return array_map(fn (array $t) => $t['label'], self::MAP);
    }
}
