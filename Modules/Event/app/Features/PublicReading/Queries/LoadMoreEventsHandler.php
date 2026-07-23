<?php

namespace Modules\Event\Features\PublicReading\Queries;

use App\Shared\Contracts\QueryHandlerInterface;
use App\Shared\Contracts\QueryInterface;
use Illuminate\Support\Collection;
use Modules\Event\Models\Event;

class LoadMoreEventsHandler implements QueryHandlerInterface
{
    /** @return array{events: Collection<int, Event>, has_more: bool} */
    public function handle(QueryInterface $query): array
    {
        /** @var LoadMoreEventsQuery $query */
        $q = Event::published()->upcoming()->with(['category', 'province', 'ward']);

        if ($query->excludeEventIds) {
            $q->whereNotIn('id', $query->excludeEventIds);
        }

        if ($query->categoryId) {
            $q->where('category_id', $query->categoryId);
        }

        // Keyset/cursor — cùng thứ tự orderBy('start_date')->orderBy('id') của
        // ListPublishedEventsHandler, nhưng so sánh "SAU" (>) vì Event sort ASC (sắp diễn ra
        // gần nhất trước), ngược chiều Post (sort DESC, so sánh "TRƯỚC" <).
        if ($query->afterStartDate !== null && $query->afterId !== null) {
            $q->where(function ($sub) use ($query) {
                $sub->where('start_date', '>', $query->afterStartDate)
                    ->orWhere(function ($tie) use ($query) {
                        $tie->where('start_date', '=', $query->afterStartDate)
                            ->where('id', '>', $query->afterId);
                    });
            });
        }

        // Lấy dư 1 dòng để biết còn nữa hay không mà không cần thêm 1 query count() riêng.
        $rows = $q->orderBy('start_date')
            ->orderBy('id')
            ->take($query->limit + 1)
            ->get();

        return [
            'events'   => $rows->take($query->limit)->values(),
            'has_more' => $rows->count() > $query->limit,
        ];
    }
}
