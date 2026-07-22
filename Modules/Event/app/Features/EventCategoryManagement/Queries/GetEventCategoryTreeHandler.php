<?php

namespace Modules\Event\Features\EventCategoryManagement\Queries;

use App\Shared\Contracts\QueryHandlerInterface;
use App\Shared\Contracts\QueryInterface;
use Illuminate\Database\Eloquent\Collection;
use Modules\Event\Models\EventCategory;

/**
 * Cây đầy đủ dựng từ danh sách phẳng (1 query) — tránh N+1 khi tải children đệ quy. Cùng pattern
 * Modules/Post/.../GetCategoryTreeHandler, Modules/Product/.../GetCategoryTreeHandler,
 * Modules/Menu/.../GetMenuTreeForAdminHandler — trước đây EventCategoryAdminController::index()
 * chỉ hiển thị danh sách phẳng dù bảng đã có parent_id, class này chưa từng tồn tại.
 */
class GetEventCategoryTreeHandler implements QueryHandlerInterface
{
    public function handle(QueryInterface $query): Collection
    {
        /** @var GetEventCategoryTreeQuery $query */
        $all = EventCategory::query()
            ->withCount('events')
            ->when($query->activeOnly, fn ($q) => $q->where('is_active', true))
            ->orderBy('sort_order')
            ->get();

        // groupBy dùng parent_id làm key — null bị PHP ép thành '' khi làm array key,
        // nên tách riêng nhóm root bằng whereNull thay vì trông cậy vào groupBy(null).
        $byParent = $all->whereNotNull('parent_id')->groupBy('parent_id');

        $attachChildren = function (EventCategory $node) use (&$attachChildren, $byParent): EventCategory {
            $children = $byParent->get($node->id, collect());
            $node->setRelation('children', $children->map($attachChildren)->values());

            return $node;
        };

        return $all->whereNull('parent_id')->map($attachChildren)->values();
    }
}
