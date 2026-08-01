<?php

namespace Modules\ContentCalendar\Enums;

/**
 * spec/ContentCalendar_Technical_Specification.md §5.3 — chỉ áp dụng cho giai đoạn TRƯỚC khi có
 * bài viết thật. Đồ thị trạng thái THUẦN, không biết gì về `post_article_id` — việc khoá
 * transition sau khi liên kết PostArticle nằm ở ChangeCalendarEntryStatusAction (§5.3.1), không
 * ở đây, để enum giữ đơn giản và tái dùng được nếu sau này có concept khác cần đồ thị này.
 */
enum CalendarEntryStatus: string
{
    case Idea     = 'idea';     // mới ghi nhận, chưa phân công
    case Planned  = 'planned';  // đã có ngày dự kiến + người viết
    case Drafting = 'drafting'; // đang viết (thường đi kèm post_article_id đã set)
    case Blocked  = 'blocked';  // vướng, cần theo dõi (thiếu tư liệu, chờ phê duyệt hướng đi...)
    case Ready    = 'ready';    // bản thảo xong, chờ vào luồng duyệt Post
    case Done     = 'done';     // bài đã publish — set tự động khi translation status = Published
    case Dropped  = 'dropped';  // huỷ kế hoạch — nên đồng bộ tay vào rejected_ideas (§10)

    public function label(): string
    {
        return match ($this) {
            self::Idea     => 'Ý tưởng',
            self::Planned  => 'Đã lên kế hoạch',
            self::Drafting => 'Đang viết',
            self::Blocked  => 'Đang vướng',
            self::Ready    => 'Sẵn sàng duyệt',
            self::Done     => 'Đã xong',
            self::Dropped  => 'Đã huỷ',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Idea     => 'badge-ghost',
            self::Planned  => 'badge-info',
            self::Drafting => 'badge-primary',
            self::Blocked  => 'badge-warning',
            self::Ready    => 'badge-accent',
            self::Done     => 'badge-success',
            self::Dropped  => 'badge-neutral',
        };
    }

    /** Validate ở tầng Action (ChangeCalendarEntryStatusAction), KHÔNG chỉ ở UI — cùng nguyên
     *  tắc TranslationStatus::canTransitionTo() (Modules\Post). */
    public function canTransitionTo(self $target): bool
    {
        return match ($this) {
            self::Idea     => in_array($target, [self::Planned, self::Dropped], true),
            self::Planned  => in_array($target, [self::Drafting, self::Dropped], true),
            self::Drafting => in_array($target, [self::Blocked, self::Ready, self::Dropped], true),
            self::Blocked  => in_array($target, [self::Drafting, self::Dropped], true),
            self::Ready    => in_array($target, [self::Drafting, self::Done], true),
            self::Done, self::Dropped => false, // terminal
        };
    }

    /** @return self[] Cột hiển thị trên board Kanban, đúng thứ tự luồng công việc. */
    public static function boardColumns(): array
    {
        return [self::Idea, self::Planned, self::Drafting, self::Blocked, self::Ready, self::Done, self::Dropped];
    }
}
