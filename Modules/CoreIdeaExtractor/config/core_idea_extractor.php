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

    // 2026-07-28 — tự động hoá "Layer 2" (§6/§12.3) qua nút bấm thủ công (KHÔNG tự động sau
    // Layer 1 — kiểm soát chi phí, xem RunLayer2ExtractionAction). max_prompt_chars chặn payload
    // quá lớn gửi lên endpoint (khác max_main_content_chars — đó là cắt bớt NỘI DUNG trích xuất,
    // đây là trần cho toàn bộ prompt TOP+MIDDLE+BOTTOM đã ghép). max_output_tokens đủ cho 2 bảng
    // Markdown tới ~25 ý tưởng kèm lý do mỗi ý (BƯỚC 1-3 của prompt).
    'layer2' => [
        'max_prompt_chars'  => 300000,
        'max_output_tokens' => 4096,

        // Goal-based loop (RunLayer2ExtractionAction) — target_idea_count khớp "Mục tiêu số
        // lượng" đã nêu trong buildLayer2PromptText() (BƯỚC 1). max_loop_iterations chặn chi phí:
        // tối đa 3 lượt gọi AI/lần bấm "Chạy Layer 2" (1 lượt đầu + tối đa 2 lượt bổ sung nếu
        // chưa đủ) — đủ dư địa cho hầu hết trường hợp thiếu vài ý, không để 1 lần bấm nút âm thầm
        // gọi AI vô hạn lượt.
        'target_idea_count'   => 10,
        'max_loop_iterations' => 3,
    ],

    // 2026-07-30 — 2 tính năng mở rộng (spec/content.md mục A+B): "Tóm tắt nội dung" và "Tái cấu
    // trúc nội dung", dùng chung `layer2.max_prompt_chars` làm trần payload prompt (cùng bản chất
    // "1 prompt build sẵn ở client" — không cần trần riêng). max_output_tokens thấp hơn Layer 2
    // vì output ngắn hơn nhiều: tóm tắt chỉ 1 đoạn <100 từ + vài gạch đầu dòng, tái cấu trúc chỉ
    // 3 đoạn ngắn theo nền tảng (Facebook/LinkedIn/Twitter), không phải bảng tới ~25 dòng ý tưởng.
    'summarization' => [
        'max_output_tokens' => 800,
    ],

    'rewrite' => [
        'max_output_tokens' => 2000,
    ],

    // Category Content Foundation (family_values/foundation.stale_after_days/existing_articles) đã
    // tách sang module dùng chung Modules\ContentFoundation — xem spec/CoreIdeaExtractor.md §12.
    // Đọc qua config('content_foundation.family_values...')/config('content_foundation.foundation...')
    // thay vì các key core_idea_extractor.family_values/core_idea_extractor.foundation cũ.
];
