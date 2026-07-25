<?php

return [
    'name' => 'CoreIdeaExtractor',

    // Ngưỡng extraction_confidence — spec/CoreIdeaExtractor.md §5.4 (v1.2: mốc DUY NHẤT
    // 200 từ, không còn vùng xám 150-199) + §9 (< 150 từ => error=true).
    'confidence' => [
        'high_min_words'     => 400,
        'high_min_headings'  => 2,
        'medium_min_words'   => 200,
        'error_max_words'    => 150,
    ],

    // Thin content trigger — spec §6.1.4 (v1.3), chỉ dùng để trả thêm gợi ý trong `notes`
    // (Layer 2 không chạy trong module này nên không có core_ideas để giới hạn số lượng thật).
    'thin_content' => [
        'max_words'    => 300,
        'max_headings' => 1,
    ],

    // Fetch HTML — timeout tính bằng giây, User-Agent minh bạch (không giả mạo browser thật),
    // cap kích thước response tránh trang quá nặng/độc hại làm nghẽn worker.
    'fetch' => [
        'timeout_seconds'   => 15,
        'max_redirects'     => 3,
        'max_content_bytes' => 5 * 1024 * 1024, // 5MB
        'user_agent'        => 'CoreIdeaExtractorBot/1.0 (+internal content research tool)',
    ],

    // Mã HTML người dùng dán tay trực tiếp (khi trang chặn crawl tự động — 403/bot protection)
    // — chặn ký tự tối đa để tránh payload quá lớn (không đi qua HTTP nên không dùng chung cap
    // 'fetch.max_content_bytes' theo byte; đây tính theo ký tự vì là input dạng string từ form).
    'paste' => [
        'max_chars' => 2_000_000,
    ],

    // Cắt main_content trả về JSON để tránh payload quá lớn khi bài viết rất dài — 20000 từng
    // cắt cụt cả bài viết bình thường (VD 1 bài ~3000 từ, 21596 ký tự đã bị cắt mất đoạn cuối),
    // nên nới lên mức chỉ chặn được trường hợp cực đoan (content root chọn nhầm nguyên trang).
    'max_main_content_chars' => 100000,

    // Batch nhiều URL cùng lúc (Http::pool) — kết quả dùng để copy nguyên JSON dán vào chat AI
    // (claude.ai), nên main_content mỗi nguồn phải cắt ngắn hơn NHIỀU so với mode 1-URL: 7 nguồn
    // x 100000 ký tự sẽ quá lớn để paste. 12000 ký tự (~2000+ từ) đủ cho phần lớn bài viết bình
    // thường, chỉ cắt bớt các bài cực dài.
    'batch' => [
        'max_urls'                          => 7,
        'max_main_content_chars_per_source' => 12000,
    ],

    // Cache HTML thô theo URL (Cache facade — dùng cache store mặc định của app, KHÔNG phải
    // bảng DB riêng, đúng tinh thần "module KHÔNG có Eloquent Model nào") — 2 batch khác nhau
    // vô tình trùng URL sẽ không fetch/tải mạng lại trong TTL này. Cache RAW HTML (trước parse)
    // chứ không cache kết quả extract đã xong, vì main_content_selector có thể khác nhau giữa
    // 2 lần gọi cùng URL — parse luôn chạy lại, chỉ bước fetch mạng là được tái sử dụng.
    // content_hash_ttl_seconds dùng cho index dedup (content_hash => url đầu tiên thấy nội dung
    // này) — TTL dài hơn vì chỉ lưu 1 chuỗi url, không tốn nhiều bộ nhớ như cache HTML.
    'cache' => [
        'enabled'                  => true,
        'fetch_ttl_seconds'        => 3600,
        'content_hash_ttl_seconds' => 86400,
    ],

    // spec/CoreIdeaExtractor.md §12.8 (v1.11) — danh sách bài ĐÃ publish trong 1 category, kéo
    // vào prompt để tránh AI đề xuất trùng ý tưởng đã viết. Chỉ tiêu đề (chuỗi ngắn) nên không có
    // rủi ro "phình" prompt như main_content — cap vẫn đặt ra theo đúng thói quen của module (mọi
    // danh sách không giới hạn đều có trần, xem batch.max_urls) để tránh truy vấn/payload runaway
    // với category có hàng nghìn bài.
    'existing_articles' => [
        'db_fetch_limit' => 100, // số bài fetch thô từ DB trước khi lọc theo status=published + sort — cần dư ra vì không phải bài nào cũng published
        'max_titles'     => 30,  // số tiêu đề tối đa đưa vào prompt sau khi đã lọc/sort
    ],
];
