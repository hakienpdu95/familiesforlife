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
];
