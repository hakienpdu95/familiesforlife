<?php

namespace Modules\ContentCalendar\Enums;

/**
 * spec/ContentCalendar_Technical_Specification.md §5.4 — chỉ để hiển thị/audit, KHÔNG rẽ nhánh
 * logic. Không dùng subject_type/subject_id kiểu polymorphic như AicemGenerationRun::subject()
 * vì Layer 2 (CoreIdeaExtractor) không có bản ghi nào để trỏ tới — biên tập viên copy tay tiêu
 * đề + lý do vào `origin_note` khi tạo entry.
 */
enum CalendarEntryOrigin: string
{
    case Manual            = 'manual';
    case CoreIdeaExtractor = 'core_idea_extractor';
    case Aicem             = 'aicem';

    public function label(): string
    {
        return match ($this) {
            self::Manual            => 'Thủ công',
            self::CoreIdeaExtractor => 'CoreIdeaExtractor (Layer 2)',
            self::Aicem              => 'Gợi ý từ Aicem',
        };
    }
}
