<?php

namespace Modules\Event\Features\PublicReading\Queries;

use App\Shared\Contracts\QueryHandlerInterface;
use App\Shared\Contracts\QueryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Event\Models\Event;

class ListPublishedEventsHandler implements QueryHandlerInterface
{
    public function handle(QueryInterface $query): LengthAwarePaginator
    {
        /** @var ListPublishedEventsQuery $query */
        $q = Event::published()->with(['category', 'province', 'ward']);

        if ($query->upcomingOnly) {
            $q->upcoming();
        }

        if ($query->categoryId) {
            $q->where('category_id', $query->categoryId);
        }

        if ($query->search) {
            $search = $query->search;
            $q->where(fn ($sub) => $sub->where('title', 'like', "%{$search}%")
                ->orWhere('short_title', 'like', "%{$search}%"));
        }

        if ($query->excludeEventIds) {
            $q->whereNotIn('id', $query->excludeEventIds);
        }

        // Sự kiện sắp diễn ra gần nhất trước — khác Post (orderByDesc published_at, bài mới
        // nhất trước) vì độc giả quan tâm "sắp có gì" hơn "vừa đăng khi nào". orderBy('id') phá
        // thế hoà khi nhiều sự kiện trùng start_date — CẦN thiết để LoadMoreEventsHandler (cursor
        // theo start_date+id) tiếp nối đúng, không lặp/bỏ sót sự kiện giữa 2 lần gọi.
        return $q->orderBy('start_date')
            ->orderBy('id')
            ->paginate($query->perPage, ['*'], 'page', $query->page)
            ->withQueryString();
    }
}
