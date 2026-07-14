<?php

namespace Modules\Menu\Features\MenuManagement\Queries;

use App\Shared\Contracts\QueryHandlerInterface;
use App\Shared\Contracts\QueryInterface;
use Illuminate\Database\Eloquent\Collection;
use Modules\Menu\Models\MenuItem;

class GetMenuTreeForAdminHandler implements QueryHandlerInterface
{
    /** Cây đầy đủ (mọi is_active) dựng từ danh sách phẳng — 1 query, tránh N+1 khi tải children đệ quy. */
    public function handle(QueryInterface $query): Collection
    {
        /** @var GetMenuTreeForAdminQuery $query */
        $all = MenuItem::query()
            ->with('category:id,name,slug,is_active')
            ->when($query->location, fn ($q) => $q->where('location', $query->location))
            ->when($query->search, fn ($q) => $q->where('label', 'like', '%' . $query->search . '%'))
            ->orderBy('sort_order')
            ->get();

        // groupBy dùng parent_id làm key — null bị PHP ép thành '' khi làm array key,
        // nên tách riêng nhóm root bằng whereNull thay vì trông cậy vào groupBy(null).
        $byParent = $all->whereNotNull('parent_id')->groupBy('parent_id');

        $attachChildren = function (MenuItem $node) use (&$attachChildren, $byParent): MenuItem {
            $children = $byParent->get($node->id, collect());
            $node->setRelation('children', $children->map($attachChildren)->values());

            return $node;
        };

        return $all->whereNull('parent_id')->map($attachChildren)->values();
    }
}
