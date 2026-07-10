<?php

// Danh sách content vertical hợp lệ — quyết định thư mục seed knowledge base mặc định
// (resources/knowledge_base_seeds/{vertical}/...) khi onboarding 1 Organization mới.
// Auto-merge vào config('aicem_content_verticals.*') — xem AicemServiceProvider::boot()
// và spec/AICEM_Technical_Specification.md mục 5.4.
return [
    'generic'         => ['label' => 'Mặc định (chưa phân loại)'],
    'news_publisher'  => ['label' => 'Tòa soạn tin tức'],
    'marketing_brand' => ['label' => 'Nhãn hàng / Marketing'],
    'ecommerce'       => ['label' => 'E-commerce / Bán lẻ'],
    'health_blog'     => ['label' => 'Blog sức khoẻ'],
];
