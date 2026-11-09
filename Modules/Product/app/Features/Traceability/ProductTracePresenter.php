<?php

namespace Modules\Product\Features\Traceability;

use App\Shared\Traceability\Contracts\TracePresenter;
use App\Shared\Traceability\TraceabilityService as T;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Modules\Post\Enums\TranslationStatus;
use Modules\Post\Models\PostArticleTranslation;
use Modules\Product\Enums\ProductStatus;
use Modules\Product\Enums\ProductType;
use Modules\Product\Models\Product;

class ProductTracePresenter implements TracePresenter
{
    public function view(): string
    {
        return 'product::traceability.product';
    }

    public function constrain(Builder $query): void
    {
        $query->withTrashed()->with(['category:id,name', 'approvalSubject']);
    }

    /** @param  Product  $target */
    public function isPublic(Model $target): bool
    {
        return $target->isPubliclyVisible() && $target->status !== ProductStatus::Inactive;
    }

    /** @param  Product  $target */
    public function snapshot(Model $target): array
    {
        $public = $target->publicContent();

        return [
            'name' => $public['name'] ?? $target->name,
            'short_description' => $public['short_description'] ?? null,
            'description' => $public['description'] ?? null,
            'category' => $target->category?->name,
            'type' => $target->type?->value,
            'sku' => $target->sku,
            'price' => $target->display_price,
            'status' => $target->status?->value,
            'image' => filled($public['cover_image_url'] ?? null) ? ['url' => $public['cover_image_url']] : null,
            'gallery' => array_values(array_filter((array) ($public['gallery_urls'] ?? []))),
        ];
    }

    /** @param  Product|null  $live */
    public function page(array $payload, ?Model $live): array
    {
        $type = ProductType::tryFrom((string) ($payload['type'] ?? '')) ?? ProductType::Physical;
        $status = ProductStatus::tryFrom((string) ($payload['status'] ?? ''));
        $content = $live ? T::tidyHtml($live->publicContent()['content'] ?? null) : null;
        $sellable = $live && in_array($status, [ProductStatus::Active, ProductStatus::OutOfStock], true);

        $availability = match (true) {
            ! $live => null,
            $status === ProductStatus::Discontinued => ['tone' => 'muted', 'label' => 'Ngừng kinh doanh', 'text' => 'Sản phẩm không còn được bán. Thông tin dưới đây để tham khảo.'],
            $status === ProductStatus::OutOfStock => ['tone' => 'warn', 'label' => 'Tạm hết hàng', 'text' => 'Gian hàng có thể chưa nhận đơn, vui lòng kiểm tra lại trước khi đặt.'],
            default => null,
        };

        return $this->base([
            'title' => $payload['name'] ?? 'Sản phẩm',
            'kind' => $type === ProductType::Service ? 'Dịch vụ' : 'Sản phẩm',
            'summary' => $payload['short_description'] ?? null,
            'category' => $payload['category'] ?? null,
            'images' => ($payload['gallery'] ?? []) ?: array_values(array_filter([$payload['image']['url'] ?? null])),
            'price' => $payload['price'] ?? null,
            'availability' => $availability,
            'specs' => array_values(array_filter([
                ['label' => 'Mã sản phẩm', 'value' => $live ? 'SP'.$live->id : null, 'mono' => true],
                ['label' => 'Mã SKU', 'value' => $payload['sku'] ?? null, 'mono' => true],
                ['label' => 'Phân loại', 'value' => $type->label()],
                ['label' => 'Danh mục', 'value' => $payload['category'] ?? null],
                ['label' => 'Giá tham khảo', 'value' => $payload['price'] ?? null],
                ['label' => 'Nhà cung cấp', 'value' => $live ? self::host($live->supplier_homepage_url) : null, 'url' => $live?->supplier_homepage_url],
            ], fn ($row) => filled($row['value']))),
            'description' => ($html = T::tidyHtml(Product::richText($payload['description'] ?? null))) && T::hasContent($html) ? $html : null,
            'content_html' => $content && T::hasContent($content) ? $content : null,
            'channels' => $sellable ? $this->channels($live) : [],
            'related' => $live ? [['title' => 'Bài viết giới thiệu sản phẩm', 'icon' => 'fa-newspaper', 'items' => $this->articleCards($live)]] : [],
        ]);
    }

    /** @param  Product  $target */
    public function pending(Model $target): array
    {
        return $this->base([
            'title' => $target->name,
            'notice' => 'Thông tin sản phẩm đang được cập nhật.',
        ]);
    }

    private function base(array $page): array
    {
        return T::page([
            'heading' => 'Truy xuất sản phẩm',
            'icon' => 'fa-box',
            'kind' => 'Sản phẩm',
            'summary' => null,
            'category' => null,
            'images' => [],
            'price' => null,
            'availability' => null,
            'specs' => [],
            'description' => null,
            'content_html' => null,
            'channels' => [],
            ...$page,
        ]);
    }

    private function channels(Product $product): array
    {
        $channels = [
            ['url' => $product->shopee_url, 'name' => 'Shopee', 'short' => 'Shopee', 'logo' => 'images/shopee.png', 'domains' => ['shopee.vn', 'shp.ee'], 'button' => 'bg-[#ee4d2d] hover:bg-[#d73211]'],
            ['url' => $product->tiktok_url, 'name' => 'TikTok Shop', 'short' => 'TikTok', 'logo' => 'images/tiktok.png', 'domains' => ['tiktok.com'], 'button' => 'bg-[#161823] hover:bg-black'],
            ['url' => $product->supplier_url, 'name' => 'Nhà cung cấp', 'short' => 'Nhà cung cấp', 'logo' => null, 'domains' => [], 'button' => 'bg-[#44403c] hover:bg-[#292524]'],
        ];

        return array_values(array_filter(array_map(function (array $c) {
            $host = self::host($c['url']);

            if (! $host) {
                return null;
            }

            $official = collect($c['domains'])->contains(fn (string $d) => $host === $d || str_ends_with($host, '.'.$d));
            $mismatch = $c['domains'] && ! $official;

            return [...$c, 'host' => $host, 'official' => $official, 'mismatch' => $mismatch, 'logo' => $c['logo'] ? asset($c['logo']) : null];
        }, $channels)));
    }

    private function articleCards(Product $product): array
    {
        return PostArticleTranslation::query()
            ->with('article.media')
            ->where('status', TranslationStatus::Published)
            ->where('locale', config('post.default_locale'))
            ->whereHas('productBlocks.items', fn ($q) => $q->where('product_id', $product->id))
            ->latest('published_at')
            ->limit(6)
            ->get()
            ->map(fn (PostArticleTranslation $t) => [
                'url' => route('post.public.article', ['slug' => $t->slug, 'id' => $t->id]),
                'title' => $t->title,
                'subtitle' => $t->published_at?->format('d/m/Y'),
                'image' => $t->article?->getFirstMediaUrl('cover', 'thumb') ?: null,
            ])->all();
    }

    private static function host(?string $url): ?string
    {
        $host = $url ? strtolower((string) parse_url($url, PHP_URL_HOST)) : '';

        return $host !== '' ? preg_replace('/^www\./', '', $host) : null;
    }
}
