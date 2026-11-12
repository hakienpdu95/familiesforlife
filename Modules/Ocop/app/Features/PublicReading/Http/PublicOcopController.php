<?php

namespace Modules\Ocop\Features\PublicReading\Http;

use App\Http\Controllers\Controller;
use App\Models\Province;
use App\Models\Ward;
use App\Services\Media\MediaUrlService;
use App\Support\I18n\PortalLocale;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Heritage\Models\HeritageSite;
use Modules\Ocop\Enums\OcopProductStatus;
use Modules\Ocop\Features\PublicReading\Queries\ListOcopPublicFacetsHandler;
use Modules\Ocop\Features\PublicReading\Queries\ListOcopPublicFacetsQuery;
use Modules\Ocop\Features\PublicReading\Queries\ListPublishedOcopProductsHandler;
use Modules\Ocop\Features\PublicReading\Queries\ListPublishedOcopProductsQuery;
use Modules\Ocop\Models\OcopCategory;
use Modules\Ocop\Models\OcopProduct;
use Modules\OcopSubject\Models\OcopSubject;

/**
 * spec/Province_Showcase_Technical_Specification.md §8 (Definition of Done #5) — trang chi tiết
 * 1 sản phẩm OCOP. index() nhận ?province= (link "Xem tất cả OCOP {tỉnh}" từ
 * <x-province.section-ocop>) — cùng convention PublicEventController.
 */
class PublicOcopController extends Controller
{
    public function index(Request $request, ListPublishedOcopProductsHandler $handler, ListOcopPublicFacetsHandler $facetHandler): View
    {
        $provinceCode = $request->string('province')->trim()->value() ?: null;
        $categoryId = $request->integer('category_id') ?: null;
        $search = $request->string('q')->trim()->value() ?: null;

        // ?ward=19753 — 1 phường/xã (radio). Chỉ nhận mã thuộc đúng tỉnh đang xem: ward không có
        // nghĩa khi chưa chọn tỉnh, mã lạ/khác tỉnh bị bỏ qua thay vì ra 0 kết quả.
        $wardParam = $request->query('ward');
        $wardParam = is_array($wardParam) ? (string) reset($wardParam) : (string) $wardParam;
        $wardCodes = $provinceCode && $wardParam !== ''
            ? Ward::where('province_code', $provinceCode)->where('ward_code', $wardParam)->pluck('ward_code')->all()
            : [];

        $stars = $request->integer('stars');
        $stars = $stars >= 1 && $stars <= 5 ? $stars : null;

        // Danh mục 3 cấp (Sản phẩm → Nhóm → Phân nhóm), sản phẩm gắn ở cấp lá — chọn 1 cấp cha
        // phải lấy cả sản phẩm của mọi cấp con, không thì chọn "SẢN PHẨM THỰC PHẨM" ra 0 kết quả.
        $categoryOptions = $this->activeCategoryOptions();
        $categoryIds = $categoryId ? $this->withDescendants($categoryOptions, $categoryId) : [];
        $categoryId = $categoryIds ? $categoryId : null;

        $products = $handler->handle(new ListPublishedOcopProductsQuery(
            provinceCode: $provinceCode,
            categoryId: $categoryId,
            categoryIds: $categoryIds,
            search: $search,
            wardCodes: $wardCodes,
            stars: $stars,
            page: max(1, $request->integer('page', 1)),
        ));

        $facets = $facetHandler->handle(new ListOcopPublicFacetsQuery($provinceCode, $categoryIds, $search, $wardCodes, $stars));

        $provinceName = $provinceCode ? Province::where('province_code', $provinceCode)->value('name') : null;

        return view('ocop::public.index', compact('products', 'categoryOptions', 'categoryId', 'facets', 'provinceCode', 'provinceName', 'wardCodes', 'stars'));
    }

    /**
     * Cây danh mục phẳng (pre-order, đúng thứ tự chính thức) chỉ gồm nhánh đang hoạt động —
     * danh mục tắt thì ẩn luôn cả nhánh con của nó.
     *
     * @return array<int, array{id: int, name: string, depth: int}>
     */
    private function activeCategoryOptions(): array
    {
        $options = [];
        $hiddenDepth = null;

        foreach (OcopCategory::flatTree() as ['category' => $category, 'depth' => $depth]) {
            if ($hiddenDepth !== null && $depth > $hiddenDepth) {
                continue;
            }
            $hiddenDepth = null;

            if (! $category->is_active) {
                $hiddenDepth = $depth;

                continue;
            }

            $options[] = ['id' => $category->id, 'name' => $category->name, 'depth' => $depth];
        }

        return $options;
    }

    /**
     * @param  array<int, array{id: int, name: string, depth: int}>  $options
     * @return int[] Danh mục đã chọn + toàn bộ con cháu (rỗng nếu id không hợp lệ/đã tắt)
     */
    private function withDescendants(array $options, int $categoryId): array
    {
        $ids = [];
        $rootDepth = null;

        foreach ($options as $option) {
            if ($rootDepth === null) {
                if ($option['id'] === $categoryId) {
                    $rootDepth = $option['depth'];
                    $ids[] = $option['id'];
                }

                continue;
            }

            if ($option['depth'] <= $rootDepth) {
                break;
            }

            $ids[] = $option['id'];
        }

        return $ids;
    }

    /** spec/I18n_Specification.md §5.4 — tra theo id, slug sai locale/đã đổi → 301 về slug đúng của locale đang xem. */
    public function show(string $slug, string $id): View|RedirectResponse
    {
        $relations = ['category.parent.parent', 'ocopSubject.media'];
        $product = OcopProduct::where('status', OcopProductStatus::Published)->with($relations)->find((int) $id)
            ?? OcopProduct::where('status', OcopProductStatus::Published)->where('slug', $slug)->with($relations)->first();

        abort_unless($product, 404);

        if ($slug !== $product->slug || (int) $id !== $product->id) {
            return redirect()->route('ocop.public.show', ['slug' => $product->slug, 'id' => $product->id], 301);
        }

        $this->declareAlternates($product);

        // spec/Heritage_Technical_Specification.md §5.2 "Quy tắc bắt buộc" — luôn qua
        // HeritageSite::published(), KHÔNG find() thẳng (cùng lý do PublicEventController::show()).
        $heritageSite = $product->heritage_site_id
            ? HeritageSite::published()->find($product->heritage_site_id)
            : null;

        $mediaUrl = app(MediaUrlService::class);
        $images = $product->getMedia(OcopProduct::IMAGE_COLLECTION)
            ->map(fn ($media) => [
                'full' => $mediaUrl->url($media, 'preview'),
                'thumb' => $mediaUrl->url($media, 'thumb'),
            ])
            ->values();

        $subject = $product->ocopSubject;
        $subjectImages = $subject
            ? $subject->getMedia(OcopSubject::IMAGE_COLLECTION)
                ->map(fn ($media) => [
                    'full' => $mediaUrl->url($media, 'medium'),
                    'thumb' => $mediaUrl->url($media, 'thumb'),
                ])
                ->values()
            : collect();

        return view('ocop::public.show', compact('product', 'heritageSite', 'images', 'subject', 'subjectImages'));
    }

    private function declareAlternates(OcopProduct $product): void
    {
        $portal = app(PortalLocale::class);
        $default = PortalLocale::default();
        $alternates = [$default => $portal->using($default, fn () => route('ocop.public.show', ['slug' => $product->originalAttribute('slug'), 'id' => $product->id]))];

        foreach (PortalLocale::prefixed() as $locale) {
            if ($product->hasTranslation($locale)) {
                $slug = $product->translationFor($locale)->slug ?: $product->originalAttribute('slug');
                $alternates[$locale] = $portal->using($locale, fn () => route('ocop.public.show', ['slug' => $slug, 'id' => $product->id]));
            }
        }

        $portal->setAlternates($alternates)->markTranslated(isset($alternates[$portal->current()]));
    }
}
