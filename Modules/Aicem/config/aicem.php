<?php

// Auto-merge vào config('aicem.*') bởi AicemServiceProvider (NWIDART: tên file == module nameLower).
return [

    // Chặn phình prompt đầu vào — áp dụng trong ResolveApplicableKnowledgeAction (mục 6.9.1).
    'prompt_bounds' => [
        'max_docs_per_type'   => 5,      // giữ tối đa N document mỗi type sau khi sắp priority
        'max_knowledge_chars' => 40_000, // tổng ký tự khối knowledge_document cho 1 lần build
        'max_blocks'          => 40,     // số PostContentBlock text tối đa đưa vào prompt
        'truncate_strategy'   => 'drop_lowest_priority', // bỏ document priority THẤP nhất trước
    ],

    // Rate limit theo user cho permission aicem.use — override theo Organization qua
    // organizations.ai_rate_limit_override (mục 13).
    'rate_limit' => [
        'per_minute' => 15,
        'per_day'    => 100,
    ],

    // Ngưỡng suy ra taxonomy price_tier cho Product (VNĐ) — dùng bởi PriceTierBucketer (mục 6.2).
    'price_tiers' => [
        'budget' => 300_000,
        'mid'    => 2_000_000,
    ],

];
