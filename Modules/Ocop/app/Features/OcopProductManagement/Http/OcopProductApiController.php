<?php

namespace Modules\Ocop\Features\OcopProductManagement\Http;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Modules\Ocop\Enums\OcopProductStatus;
use Modules\Ocop\Features\OcopProductManagement\Http\Resources\OcopProductListResource;
use Modules\Ocop\Features\OcopProductManagement\Queries\ListOcopProductsForAdminHandler;
use Modules\Ocop\Features\OcopProductManagement\Queries\ListOcopProductsForAdminQuery;
use Modules\Ocop\Models\OcopProduct;
use Modules\Product\Enums\ProductLinkType;

/** JSON backend cho Tabulator ở dashboard/ocop/products — cùng pattern ArticleApiController. */
class OcopProductApiController extends Controller
{
    public function index(Request $request, ListOcopProductsForAdminHandler $handler): JsonResponse
    {
        $this->authorize('viewAny', OcopProduct::class);

        $validated = $request->validate([
            'page' => ['nullable', 'integer', 'min:1'],
            'size' => ['nullable', 'integer', 'min:5', 'max:100'],
            'search' => ['nullable', 'string', 'max:200'],
            'category_id' => ['nullable', 'integer'],
            'status' => ['nullable', 'string', 'in:'.implode(',', array_column(OcopProductStatus::cases(), 'value'))],
            'trashed' => ['nullable', 'boolean'],
        ]);

        $sortRaw = $request->input('sort.0');
        $sortField = is_array($sortRaw) ? (string) ($sortRaw['field'] ?? 'created_at') : 'created_at';
        $sortDir = is_array($sortRaw) && ($sortRaw['dir'] ?? '') === 'asc' ? 'asc' : 'desc';

        $query = new ListOcopProductsForAdminQuery(
            search: $validated['search'] ?? null,
            categoryId: $validated['category_id'] ?? null,
            status: $validated['status'] ?? null,
            page: max(1, (int) ($validated['page'] ?? 1)),
            perPage: min(100, max(5, (int) ($validated['size'] ?? 20))),
            sortField: $sortField,
            sortDir: $sortDir,
            trashed: (bool) ($validated['trashed'] ?? false),
        );

        $paginator = $handler->handle($query);

        return response()->json([
            'data' => OcopProductListResource::collection($paginator->items()),
            'last_page' => $paginator->lastPage(),
            'total' => $paginator->total(),
            'trashed_count' => OcopProduct::onlyTrashed()->count(),
        ]);
    }

    /** Ô tìm sản phẩm trong khối "Sản phẩm OCOP" của bài viết — cùng shape ProductPickerResource. */
    public function blockPicker(Request $request): JsonResponse
    {
        $this->authorize('viewAny', OcopProduct::class);

        $keyword = trim((string) $request->string('q'));

        $paginator = OcopProduct::query()
            ->with('media')
            ->when($keyword !== '', fn ($q) => $q->where('name', 'like', "%{$keyword}%"))
            ->orderByRaw("status = 'published' desc")
            ->orderByDesc('star_rating')
            ->orderBy('name')
            ->paginate(min(50, max(5, $request->integer('per_page', 20))));

        $links = [ProductLinkType::Shopee, ProductLinkType::TikTok];

        return response()->json([
            'data' => collect($paginator->items())->map(fn (OcopProduct $p) => [
                'id' => $p->id,
                'name' => $p->name,
                'short_description' => Str::limit(trim(strip_tags((string) $p->description)), 160),
                'price_label' => $p->star_rating ? 'OCOP '.$p->star_rating.' sao' : null,
                'cover_image_url' => $p->imageUrl('thumb') ?: null,
                'status' => $p->status->value,
                'available_links' => collect($links)
                    ->filter(fn (ProductLinkType $t) => filled($p->{$t->urlColumn()}))
                    ->map(fn (ProductLinkType $t) => ['type' => $t->value, 'label' => $t->label()])
                    ->values(),
            ]),
            'last_page' => $paginator->lastPage(),
            'total' => $paginator->total(),
        ]);
    }
}
