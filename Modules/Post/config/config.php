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

    // spec/Related_Posts_Engine_Technical_Specification.md — thuật toán gợi ý bài viết liên
    // quan. Đổi trọng số ở đây KHÔNG cần sửa code, chỉ cần deploy lại config (không có UI chỉnh
    // ở v1, §0).
    'related_posts' => [
        'max_results'            => 6,   // số bài hiển thị trong khối "Bài viết liên quan"
        'candidate_pool_limit'   => 200, // chặn trần số ứng viên đưa vào tính điểm PHP (§5.2), tránh quét toàn bảng khi site có hàng chục nghìn bài
        'cache_ttl_hours'        => 6,   // §0 "Thời điểm tính gợi ý" — TTL cache theo article_id
        'behavior_lookback_days' => 90,  // cửa sổ thời gian tính đồng-xem (§5.3) — cũng là retention của post_article_view_events (§6.2)
        'visitor_cookie_name'    => 'rp_vid',
        'visitor_cookie_days'    => 365,

        'weights' => [
            'category_primary'     => 40, // 2 bài cùng danh mục CHÍNH (is_primary=true)
            'category_secondary'   => 20, // chỉ trùng danh mục phụ (không phải is_primary)
            'tag_per_match'        => 15, // mỗi tag trùng — nhân với số tag trùng, chặn trần ở tag_match_cap
            'tag_match_cap'        => 3,  // trùng từ tag thứ 4 trở đi không cộng thêm điểm (tránh bài "nhồi tag" thắng áp đảo)
            'behavior_per_covisit' => 5,  // mỗi lượt "đồng-xem" (session khác nhau) — nhân với số lượt, chặn trần ở behavior_covisit_cap
            'behavior_covisit_cap' => 10, // trùng lượt đồng-xem thứ 11 trở đi không cộng thêm (chặn 1 bài viral áp đảo mọi gợi ý)
            'popularity'           => 8,  // nhân với log10(1 + view_count) — điểm phụ/tie-break, KHÔNG để 1 bài cực hot thắng mọi bài mới đúng chủ đề hơn
        ],
    ],

    // spec/Breaking_News_Ticker_Technical_Specification.md — dải ticker "tin nóng" ghim đầu
    // trang chủ.
    'breaking_news' => [
        'max_ticker_items'       => 8,   // tối đa số tin trong vòng xoay (thừa thì không hiển thị, không lỗi)
        'rotate_seconds'         => 5,   // mỗi tiêu đề hiện bao lâu trước khi chuyển sang tiêu đề kế tiếp
        'poll_seconds'           => 60,  // tần suất ticker tự kiểm tra danh sách mới qua JSON (§7.3)
        'default_badge_label'    => 'NÓNG',
        'default_duration_hours' => 48, // chỉ gợi ý prefill ends_at trên form admin, KHÔNG ép validate
    ],
];
