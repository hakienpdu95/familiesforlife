<?php

return [
    'name' => 'Video',

    // spec/Video_Management_Technical_Specification.md §0/§4.2 — chế độ "privacy-enhanced" của
    // YouTube (giảm cookie theo dõi khi khách chưa tương tác với video). Đổi thành
    // 'www.youtube.com' nếu team không cần.
    'embed_domain' => 'www.youtube-nocookie.com',

    // Số video/trang ở trang công khai (§7.1) — tách khỏi code để đổi không cần deploy lại logic.
    'per_page' => 12,

    // Danh sách host YouTube hợp lệ — NGUỒN DUY NHẤT, đọc bởi CẢ 2 nơi cần whitelist domain:
    // Video::getWatchUrlAttribute() (§4.1) VÀ ResolveYoutubeVideoIdAction::isWhitelistedHost()
    // (§4.3/§5.2, validate lúc lưu). Sửa 1 chỗ duy nhất trong file này nếu YouTube thêm/đổi domain.
    'allowed_hosts' => [
        'youtube.com', 'www.youtube.com', 'm.youtube.com', 'music.youtube.com', 'youtu.be',
    ],
];
