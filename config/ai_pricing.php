<?php

// Giá USD / 1.000.000 token — cập nhật khi provider đổi bảng giá, KHÔNG hard-code trong code.
// Thiếu giá cho 1 model đang dùng → CostCalculator throw UnknownModelPricingException
// (không âm thầm trả 0.0 — xem AICEM_Technical_Specification.md mục 8.7).
//
// cache_write/cache_read (Phase 6, mục 15) — giá riêng cho prompt caching Anthropic (5 phút TTL
// mặc định): cache_write ~1.25x giá input thường (phải ghi vào cache), cache_read ~0.1x giá input
// thường (tái dùng cache có sẵn). OpenAI chưa cấu hình 2 khoá này — caching của OpenAI tự động,
// đánh giá tích hợp giá riêng ở phase sau khi cần (mục 15).
return [
    'openai' => [
        'gpt-4.1'      => ['input' => 2.00, 'output' => 8.00],
        'gpt-4.1-mini' => ['input' => 0.40, 'output' => 1.60],
    ],
    'anthropic' => [
        'claude-sonnet-5'  => ['input' => 3.00, 'output' => 15.00, 'cache_write' => 3.75, 'cache_read' => 0.30],
        'claude-haiku-4-5' => ['input' => 0.80, 'output' => 4.00, 'cache_write' => 1.00, 'cache_read' => 0.08],
    ],
];
