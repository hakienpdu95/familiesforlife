<?php

return [
    'name' => 'ProvinceShowcase',

    // spec/Province_Showcase_Technical_Specification.md §4.3 — tỉnh nào bật trang chuyên đề,
    // khoá là provinces.slug. Thêm tỉnh mới = thêm 1 dòng + (tuỳ chọn) tạo view riêng, KHÔNG
    // cần thao tác DB nào khác ngoài seed nội dung.
    'showcase_provinces' => [
        'hue' => [
            'tagline'      => 'Di sản, văn hóa và ẩm thực Cố đô',
            'accent_color' => '#7a1f2b', // đỏ son cung đình — dùng khi có custom view
        ],
        'ca-mau' => [
            'tagline'      => 'Đất Mũi, rừng ngập mặn và hương vị phương Nam',
            'accent_color' => '#0f6e4f', // xanh rừng đước
        ],
    ],

    // Số item tối đa mỗi khối trên trang landing.
    'section_limit' => 6,
];
