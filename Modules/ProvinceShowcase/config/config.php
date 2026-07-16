<?php

return [
    'name' => 'ProvinceShowcase',

    // spec/Province_Showcase_Technical_Specification.md §4.3 — tỉnh nào cần tagline/accent_color
    // riêng (và/hoặc custom view), khoá là provinces.slug. Trang chuyên đề KHÔNG còn giới hạn
    // theo whitelist này — mọi tỉnh/thành trong bảng provinces đều truy cập được qua
    // /{type}/{slug}; tỉnh chưa có mặt ở đây chỉ đơn giản dùng bộ tagline/accent_color mặc định
    // (khoá 'default' bên dưới).
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

    // Dùng cho tỉnh/thành không có mặt trong showcase_provinces ở trên.
    'default' => [
        'tagline'      => 'Khám phá di sản, văn hóa, ẩm thực và sản phẩm đặc trưng địa phương',
        'accent_color' => '#1d4ed8',
    ],

    // Số item tối đa mỗi khối trên trang landing.
    'section_limit' => 6,
];
