<?php

namespace Modules\CoreIdeaExtractor\Enums;

/**
 * spec/CoreIdeaExtractor.md §5.4 (v1.2) — mốc DUY NHẤT: main_content < 200 từ luôn là Low,
 * không còn vùng xám. Chỉ dùng in-memory (không có cột DB nào — module không persist gì).
 */
enum ExtractionConfidence: string
{
    case High   = 'high';
    case Medium = 'medium';
    case Low    = 'low';

    public function label(): string
    {
        return match ($this) {
            self::High   => 'Cao',
            self::Medium => 'Trung bình',
            self::Low    => 'Thấp',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::High   => 'badge-success',
            self::Medium => 'badge-warning',
            self::Low    => 'badge-error',
        };
    }
}
