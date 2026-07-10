<?php

namespace Modules\Aicem\Enums;

/**
 * Nhân bản tinh thần của Modules\WorkflowAutomation\Enums\ConditionMatch (All|Any|None) cho
 * bài toán "1 tập điều kiện scope, khớp kiểu AND hay OR" của knowledge document (mục 5.2/6.7) —
 * không import module đó để tránh phụ thuộc chéo vào 1 module còn placeholder. Không cần case
 * None vì scope = null đã thay thế "không điều kiện".
 */
enum ScopeMatch: string
{
    case Any = 'any';
    case All = 'all';
}
