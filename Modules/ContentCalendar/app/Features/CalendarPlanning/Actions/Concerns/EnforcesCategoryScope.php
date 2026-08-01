<?php

namespace Modules\ContentCalendar\Features\CalendarPlanning\Actions\Concerns;

use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;

/**
 * spec/ContentCalendar_Technical_Specification.md §6.4 — dùng chung bởi CreateCalendarEntryAction
 * VÀ UpdateCalendarEntryAction (khi đổi post_category_id của entry đã có) để tránh 2 nơi viết lại
 * cùng 1 điều kiện theo 2 cách khác nhau rồi lệch nhau về sau.
 */
trait EnforcesCategoryScope
{
    /** @throws AuthorizationException */
    protected function assertCategoryInScope(User $actor, int $categoryId): void
    {
        if (
            $actor->isPlatformSectionEditor()
            && ! $actor->isPlatformContentEditor()
            && ! $actor->isPlatformContentHead()
            && ! $actor->postCategoryEditorships()->where('post_categories.id', $categoryId)->exists()
        ) {
            throw new AuthorizationException('Category ngoài phạm vi phụ trách.');
        }
    }
}
