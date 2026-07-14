<?php

namespace Modules\Event\Features\EventCategoryManagement\Queries;

use App\Shared\Contracts\QueryHandlerInterface;
use App\Shared\Contracts\QueryInterface;
use Illuminate\Database\Eloquent\Collection;
use Modules\Event\Models\EventCategory;

class ListEventCategoriesForAdminHandler implements QueryHandlerInterface
{
    /** Danh sách phẳng (kèm parent + đếm sự kiện) — dùng cho select cascading & bảng quản trị. */
    public function handle(QueryInterface $query): Collection
    {
        /** @var ListEventCategoriesForAdminQuery $query */
        return EventCategory::query()
            ->with('parent:id,name')
            ->withCount('events')
            ->when($query->search, fn ($q) => $q->where('name', 'like', '%' . $query->search . '%'))
            ->orderBy('sort_order')
            ->get();
    }
}
