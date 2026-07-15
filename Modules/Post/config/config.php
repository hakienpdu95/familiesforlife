<?php

return [
    'name' => 'Post',

    'locales' => [
        'vi' => 'Tiếng Việt',
        'en' => 'English',
    ],
    'default_locale' => 'vi',

    // "Xem thêm bài viết" (trang chủ) dừng hẳn khi đã tải đủ ngần này bài — dùng chung bởi
    // PublicCategoryController (chặn server-side) và home.blade.php (khởi tạo Alpine), tránh
    // lặp lại 1 con số ma thuật ở 2 nơi.
    'load_more_max_total' => 288,
];
