<?php

namespace Modules\Product\Features\CatalogManagement\Queries;

use App\Shared\Contracts\QueryHandlerInterface;
use App\Shared\Contracts\QueryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Product\Models\Product;

class ListProductsForAdminHandler implements QueryHandlerInterface
{
    private const SORTABLE = ['name', 'price', 'sort_order', 'created_at'];

    public function handle(QueryInterface $query): LengthAwarePaginator
    {
        /** @var ListProductsForAdminQuery $query */
        // 'approvalSubject' bắt buộc eager-load — Blade danh sách hiển thị badge duyệt nội
        // dung cho từng dòng (spec/Workflow_Approval_Technical_Specification.md §9); thiếu
        // dòng này sẽ ném LazyLoadingViolationException (strict mode) khi danh sách có ≥ 2
        // sản phẩm và Blade gọi $p->approvalStatus() cho từng dòng.
        $q = Product::query()->with(['category:id,name', 'approvalSubject']);

        if ($query->search) {
            $term = '%' . $query->search . '%';
            $q->where(function ($sub) use ($term) {
                $sub->where('name', 'like', $term)
                    ->orWhere('sku', 'like', $term);
            });
        }

        if ($query->categoryId) {
            $q->where('category_id', $query->categoryId);
        }

        if ($query->status) {
            $q->where('status', $query->status);
        }

        if ($query->type) {
            $q->where('type', $query->type);
        }

        $sortField = in_array($query->sortField, self::SORTABLE, true) ? $query->sortField : 'sort_order';
        $sortDir   = $query->sortDir === 'desc' ? 'desc' : 'asc';

        return $q->orderBy($sortField, $sortDir)
            ->orderByDesc('created_at')
            ->paginate($query->perPage, ['*'], 'page', $query->page)
            ->withQueryString();
    }
}
