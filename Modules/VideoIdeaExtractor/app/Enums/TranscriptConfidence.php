<?php

namespace Modules\VideoIdeaExtractor\Enums;

/**
 * Tương đương ExtractionConfidence bên CoreIdeaExtractor nhưng KHÔNG dùng chung enum đó — 2 module
 * độc lập hoàn toàn ngoài ContentFoundation (xem quyết định thiết kế "tách riêng" của module này).
 * Ngưỡng đo trên word_count của transcript đã làm sạch — KHÔNG có heading/table để cộng thêm điều
 * kiện như bên CoreIdeaExtractor (transcript không có cấu trúc HTML).
 */
enum TranscriptConfidence: string
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
