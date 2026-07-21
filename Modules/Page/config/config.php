<?php

return [
    'name' => 'Page',

    // spec/Page_Static_Pages_Technical_Specification.md §4.1/§4.1.1 — slug KHÔNG được trùng
    // với các route GET 1-segment ở gốc domain (ngoài dashboard/*, api/* — 2 prefix này không
    // bao giờ va với Page). Danh sách lấy từ `php artisan route:list --method=GET` tại thời
    // điểm viết module — PHẢI review lại mỗi khi có module khác thêm route GET mới ở gốc
    // (xem PageReservedSlugsTest, chạy trong CI để phát hiện lệch tự động, không chỉ review
    // thủ công).
    'reserved_slugs' => [
        'login', 'logout', 'register', 'home', 'dashboard', 'api', 'up',
        'email', 'profile', 'me', 'auth', 'notifications', 'storage',
        'forgot-password', 'reset-password', 'confirm-password',
        'two-factor-challenge', 'user',
    ],
];
