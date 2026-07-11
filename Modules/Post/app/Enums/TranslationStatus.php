<?php

namespace Modules\Post\Enums;

/**
 * Trạng thái xuất bản PER-LOCALE (trên PostArticleTranslation), thay cho ArticleStatus cũ
 * từng nằm trên PostArticle. Xem spec/PublishingEngine_Technical_Specification.md §5.
 */
enum TranslationStatus: string
{
    case Draft       = 'draft';
    case Submitted   = 'submitted';
    case Approved    = 'approved';
    case Scheduled   = 'scheduled';
    case Published   = 'published';
    case Unpublished = 'unpublished';
    case Archived    = 'archived';

    public function label(): string
    {
        return match ($this) {
            self::Draft       => 'Nháp',
            self::Submitted   => 'Chờ duyệt',
            self::Approved    => 'Đã duyệt',
            self::Scheduled   => 'Đã lên lịch',
            self::Published   => 'Đã xuất bản',
            self::Unpublished => 'Đã gỡ',
            self::Archived    => 'Lưu trữ',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Draft       => 'badge-ghost',
            self::Submitted   => 'badge-warning',
            self::Approved    => 'badge-info',
            self::Scheduled   => 'badge-info',
            self::Published   => 'badge-success',
            self::Unpublished => 'badge-error',
            self::Archived    => 'badge-neutral',
        };
    }

    /** Transition hợp lệ — validate ở tầng Action, KHÔNG chỉ ở UI. */
    public function canTransitionTo(self $target): bool
    {
        return match ($this) {
            self::Draft       => in_array($target, [self::Submitted, self::Scheduled], true),
            self::Submitted   => in_array($target, [self::Approved, self::Draft], true),
            self::Approved    => in_array($target, [self::Scheduled, self::Published], true),
            self::Scheduled   => in_array($target, [self::Published, self::Draft], true),
            self::Published   => in_array($target, [self::Unpublished, self::Archived], true),
            self::Unpublished => in_array($target, [self::Published, self::Archived], true),
            self::Archived    => false,
        };
    }
}
