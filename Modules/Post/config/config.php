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

    // spec/Post_VersionHistory_Technical_Specification.md §10 — retention/giới hạn version,
    // TẮT MẶC ĐỊNH (null = không giới hạn). Không bao giờ prune version trigger=publish/restore
    // (VersionTrigger::isProtectedFromPruning, §7).
    'version_history' => [
        'retention_days'                => null, // null = giữ vĩnh viễn. Đặt số nguyên để bật `post:prune-article-versions`.
        'max_versions_per_translation'  => null, // null = không giới hạn. Đặt vd 80-100 để tự dọn version "save" cũ ngay sau mỗi lần ghi (§10.1).
    ],
];
