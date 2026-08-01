<?php

namespace Modules\ContentCalendar\Features\CalendarPlanning\Queries;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\ContentCalendar\Enums\CalendarEntryStatus;
use Modules\ContentCalendar\Features\CalendarPlanning\Data\CalendarBoardFilterData;
use Modules\ContentCalendar\Models\ContentCalendarEntry;

/**
 * spec/ContentCalendar_Technical_Specification.md §7.1 — eager-load + ownership scope ở tầng SQL
 * + phân trang bắt buộc, KHÔNG phải tuỳ chọn (xem docblock §7.1 cho lý do chi tiết từng điểm).
 */
class ListCalendarEntriesAction
{
    use AsAction;

    public function handle(User $viewer, CalendarBoardFilterData $filter): LengthAwarePaginator
    {
        return ContentCalendarEntry::query()
            // postArticle.translations — PostArticle::mainTranslation() đọc qua property
            // `translations` đã eager-load (không re-query), tránh N+1/LazyLoadingViolationException
            // khi ContentCalendarEntry::displayStatusLabel() chạy cho mỗi dòng trên board (§5.2).
            ->with(['category', 'assignedTo', 'postArticle.translations'])
            ->when(! $filter->includeDone, fn (Builder $q) => $q->whereNotIn('status', [
                CalendarEntryStatus::Done->value,
                CalendarEntryStatus::Dropped->value,
            ]))
            ->when($filter->categoryId, fn (Builder $q) => $q->where('post_category_id', $filter->categoryId))
            ->when($filter->assignedTo, fn (Builder $q) => $q->where('assigned_to', $filter->assignedTo))
            ->when($filter->from, fn (Builder $q) => $q->where('target_publish_date', '>=', $filter->from))
            ->when($filter->to, fn (Builder $q) => $q->where('target_publish_date', '<=', $filter->to))
            ->tap(fn (Builder $q) => $this->applyOwnershipScope($q, $viewer))
            ->orderByRaw('target_publish_date IS NULL, target_publish_date')
            ->paginate($filter->perPage);
    }

    /**
     * Đúng lát cắt Policy::view() (§6.3) nhưng áp dụng NGAY TRONG SQL — không "query hết rồi lọc
     * bằng PHP" (§7.1). content_editor/content_head/platform_viewer thấy mọi category;
     * section_editor chỉ thấy category được gán qua post_category_editors; còn lại (vd
     * platform_content_creator) chỉ thấy entry của chính mình.
     */
    private function applyOwnershipScope(Builder $query, User $viewer): void
    {
        if ($viewer->isPlatformContentEditor() || $viewer->isPlatformContentHead() || $viewer->isPlatformViewer()) {
            return;
        }

        if ($viewer->isPlatformSectionEditor()) {
            $categoryIds = $viewer->postCategoryEditorships()->pluck('post_categories.id');
            $query->whereIn('post_category_id', $categoryIds);

            return;
        }

        $query->where(function (Builder $q) use ($viewer) {
            $q->where('created_by', $viewer->id)->orWhere('assigned_to', $viewer->id);
        });
    }
}
