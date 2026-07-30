<?php

return [
    'name' => 'AccessTrade',

    // Tham số hành vi đồng bộ (KHÔNG chứa credential — access_token/base_url ở
    // config/services.php, đúng chỗ anthropic/resend/turnstile đang dùng). 'merchants' rỗng
    // nghĩa là không filter theo merchant, gọi API 1 lần không truyền tham số merchant.
    'offers' => [
        'per_page'  => 50,
        'max_pages' => 50, // trần an toàn ~2500 offer/lần chạy, tránh vòng lặp phân trang bất tận
        'merchants' => [],
    ],

    'top_products' => [
        'merchants' => [],
        'days_back' => 30, // khoảng ngày date_from..date_to (hôm nay) truyền cho API mỗi lần đồng bộ
    ],
];
