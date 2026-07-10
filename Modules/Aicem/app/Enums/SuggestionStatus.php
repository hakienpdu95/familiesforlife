<?php

namespace Modules\Aicem\Enums;

/**
 * stale: nội dung subject (field/block) đã đổi từ lúc AI phân tích tới lúc editor bấm accept
 * (generation chạy nền qua queue, có độ trễ) — không accept trực tiếp, buộc editor xác nhận lại
 * (spec/AICEM_Technical_Specification.md mục 9.1).
 */
enum SuggestionStatus: string
{
    case Pending  = 'pending';
    case Accepted = 'accepted';
    case Rejected = 'rejected';
    case Stale    = 'stale';
}
