<?php

return [
    'name' => 'VideoIdeaExtractor',

    // Ngưỡng extraction_confidence cho transcript — KHÁC CoreIdeaExtractor (không có heading/table
    // để tính, transcript nói thường dài hơn bài viết cùng nội dung do văn nói lặp/đệm từ) — xem
    // Modules\VideoIdeaExtractor\Enums\TranscriptConfidence.
    'confidence' => [
        'high_min_words'   => 800,
        'medium_min_words' => 150,
    ],

    // Mã transcript người dùng dán tay — chặn ký tự tối đa để tránh payload quá lớn. Cao hơn nhiều
    // so với 'paste.max_chars' của CoreIdeaExtractor (2_000_000, dùng cho HTML) vì đây LUÔN là input
    // dạng transcript thô (không có thẻ HTML/script để cắt bớt) — video 1-2h có thể transcript rất dài.
    'paste' => [
        'max_chars' => 500_000,
    ],

    // Batch nhiều video cùng lúc — kết quả dùng để copy nguyên JSON dán vào chat AI, nên số lượng
    // tối đa thấp hơn nhiều so với batch.max_urls=7 của CoreIdeaExtractor: transcript 1 video dài
    // hơn hẳn 1 bài viết trung bình, nhiều video full transcript sẽ vượt quá kích thước hợp lý để
    // paste dù mỗi video đã cắt theo max_transcript_chars_per_video.
    'batch' => [
        'max_videos'                       => 5,
        'max_transcript_chars_per_video'   => 20000,
    ],

    // 2026-08 — tự động hoá "Layer 2" qua nút bấm thủ công (KHÔNG tự động sau Layer 1 — kiểm soát
    // chi phí, cùng nguyên tắc CoreIdeaExtractor). max_prompt_chars chặn payload quá lớn gửi lên
    // endpoint. max_output_tokens đủ cho 1 lượt gọi (~25 ý tưởng có cấu trúc kèm lý do mỗi ý).
    'layer2' => [
        'max_prompt_chars'  => 300000,
        'max_output_tokens' => 4096,

        // Goal-based loop (RunVideoIdeaLayer2Action) — target_idea_count khớp "Mục tiêu số lượng"
        // đã nêu trong buildLayer2PromptText() (BƯỚC 1). max_loop_iterations chặn chi phí: tối đa
        // 3 lượt gọi AI/lần bấm "Chạy AI" (1 lượt đầu + tối đa 2 lượt bổ sung nếu chưa đủ) — đủ dư
        // địa cho hầu hết trường hợp thiếu vài ý, không để 1 lần bấm nút âm thầm gọi AI vô hạn lượt.
        'target_idea_count'   => 8,
        'max_loop_iterations' => 3,
    ],

    // 2026-08 — 3 tính năng mở rộng thao tác trên ĐÚNG 1 video đã trích xuất (khác Layer 2 — sinh
    // ý tưởng từ CẢ batch): "Tiêu đề & Thumbnail", "Hook mở đầu", "Ý tưởng Shorts". Dùng chung
    // 'layer2.max_prompt_chars' làm trần payload prompt (cùng bản chất "1 prompt build sẵn ở
    // client", không cần trần riêng). max_output_tokens thấp hơn Layer 2 vì output ngắn hơn nhiều:
    // mỗi tính năng chỉ 1 bảng ~5 dòng, không phải bảng tới ~25 dòng ý tưởng.
    'titles' => [
        'max_output_tokens' => 800,
    ],

    'hooks' => [
        'max_output_tokens' => 800,
    ],

    'shorts' => [
        'max_output_tokens' => 1000,
    ],

    // 2026-08 — 3 tính năng "kịch bản", chạy SAU khi đã chốt tiêu đề/hook (khác nhóm
    // titles/hooks/shorts vốn là bước CHỌN phương án): "Dàn ý thân bài" (outline), "CTA & giữ chân"
    // (cta), "Biên tập lời nói" (polish). Dùng chung 'layer2.max_prompt_chars' làm trần payload.
    // max_output_tokens cao hơn nhóm trên vì output dài hơn hẳn: outline liệt kê từng phần kèm câu
    // chuyển tiếp + rủi ro tụt xem, không phải bảng 5 dòng ngắn.
    'outline' => [
        'max_output_tokens' => 2000,
    ],

    'cta' => [
        'max_output_tokens' => 1000,
    ],

    'polish' => [
        // Bản nháp kịch bản người dùng dán — thấp hơn NHIỀU so với 'paste.max_chars' (vốn cho
        // transcript cả video 1-2h) vì toàn bộ bản nháp phải được TRẢ LẠI nguyên văn trong output:
        // trần input này phải cân với max_output_tokens ngay dưới, không nới tự do được.
        // ~12k ký tự tiếng Việt ≈ 2.2k từ ≈ 15 phút đọc thành lời — đủ cho 1 kịch bản video dài.
        'max_draft_chars'   => 12000,
        'max_output_tokens' => 4096,
    ],
];
