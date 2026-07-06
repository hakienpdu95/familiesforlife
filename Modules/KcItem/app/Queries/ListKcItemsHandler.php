<?php

namespace Modules\KcItem\Queries;

use App\Shared\Contracts\QueryHandlerInterface;
use App\Shared\Contracts\QueryInterface;
use App\Shared\Tenancy\TenantContext;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Modules\KcItem\Models\KcItem;
use Modules\KcItem\Services\KcItemAccessService;

class ListKcItemsHandler implements QueryHandlerInterface
{
    private const SORTABLE = [
        'title', 'type', 'status', 'visibility', 'view_count', 'version', 'created_at', 'updated_at',
    ];

    public function __construct(private KcItemAccessService $accessService) {}

    public function handle(QueryInterface $query): LengthAwarePaginator
    {
        /** @var ListKcItemsQuery $query */

        $sortField = in_array($query->sortField, self::SORTABLE, true)
            ? $query->sortField
            : 'created_at';

        $sortDir = $query->sortDir === 'asc' ? 'asc' : 'desc';

        $q = KcItem::withoutTenant()
            ->select('kc_items.*')
            ->with(['category:id,name,color_hex', 'owner:id,name'])
            ->where('kc_items.organization_id', TenantContext::getOrganizationId());

        // Áp dụng visibility scope cho non-admin users
        if (auth()->check() && ! auth()->user()->hasRole('System_Admin')) {
            $this->accessService->applyVisibilityScope($q, auth()->user());
        }

        // ── Text search ─────────────────────────────────────────────────────
        if ($query->search !== null && $query->search !== '') {
            $term = '%' . $query->search . '%';
            $q->where(function (Builder $sub) use ($term): void {
                $sub->where('kc_items.title',   'like', $term)
                    ->orWhere('kc_items.summary', 'like', $term);
            });
        }

        // ── Exact filters ────────────────────────────────────────────────────
        if ($query->status !== null && $query->status !== '') {
            $q->where('kc_items.status', $query->status);
        }

        if ($query->type !== null && $query->type !== '') {
            $q->where('kc_items.type', $query->type);
        }

        if ($query->categoryId !== null) {
            $q->where('kc_items.category_id', $query->categoryId);
        }

        if ($query->visibility !== null && $query->visibility !== '') {
            $q->where('kc_items.visibility', $query->visibility);
        }

        // ── Date range ───────────────────────────────────────────────────────
        if ($query->dateFrom !== null && $query->dateFrom !== '') {
            $q->whereDate('kc_items.created_at', '>=', $query->dateFrom);
        }

        if ($query->dateTo !== null && $query->dateTo !== '') {
            $q->whereDate('kc_items.created_at', '<=', $query->dateTo);
        }

        if ($query->tagId !== null) {
            $q->whereHas('tags', fn ($sub) => $sub->where('kc_tags.id', $query->tagId));
        }

        // ── Sort ─────────────────────────────────────────────────────────────
        $q->orderBy('kc_items.' . $sortField, $sortDir);

        if ($sortField !== 'id') {
            $q->orderBy('kc_items.id', $sortDir);
        }

        return $q->paginate($query->perPage, ['*'], 'page', $query->page);
    }
}
