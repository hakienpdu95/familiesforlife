<?php

namespace Modules\Product\Features\Traceability;

use App\Models\Media;
use App\Services\Media\MediaUrlService;
use App\Shared\Traceability\Contracts\TracePresenter;
use App\Shared\Traceability\TraceabilityService as T;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
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
            $status === ProductStatus::Discontinued => ['tone' => 'muted', 'label' => __('Ngừng kinh doanh'), 'text' => __('Sản phẩm không còn được bán. Thông tin dưới đây để tham khảo.')],
            $status === ProductStatus::OutOfStock => ['tone' => 'warn', 'label' => __('Tạm hết hàng'), 'text' => __('Gian hàng có thể chưa nhận đơn, vui lòng kiểm tra lại trước khi đặt.')],
            default => null,
        };

        return $this->base([
            'title' => $payload['name'] ?? __('Sản phẩm'),
            'kind' => $type === ProductType::Service ? __('Dịch vụ') : __('Sản phẩm'),
            'summary' => $payload['short_description'] ?? null,
            'category' => $payload['category'] ?? null,
            'gallery' => $this->gallery(($payload['gallery'] ?? []) ?: array_values(array_filter([$payload['image']['url'] ?? null]))),
            'price' => $payload['price'] ?? null,
            'availability' => $availability,
            'description' => ($html = T::tidyHtml(Product::richText($payload['description'] ?? null))) && T::hasContent($html) ? $html : null,
            'content_html' => $content && T::hasContent($content) ? $content : null,
            'channels' => $sellable ? $this->channels($live) : [],
        ]);
    }

    /** @param  Product  $target */
    public function pending(Model $target): array
    {
        return $this->base([
            'title' => $target->name,
            'notice' => __('Thông tin sản phẩm đang được cập nhật.'),
        ]);
    }

    private function base(array $page): array
    {
        return T::page([
            'heading' => __('Truy xuất sản phẩm'),
            'icon' => 'fa-box',
            'kind' => __('Sản phẩm'),
            'summary' => null,
            'category' => null,
            'gallery' => [],
            'price' => null,
            'availability' => null,
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
            ['url' => $product->supplier_url, 'name' => 'Nhà cung cấp', 'short' => __('Nhà cung cấp'), 'logo' => null, 'domains' => [], 'button' => 'bg-[#44403c] hover:bg-[#292524]'],
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

    private function gallery(array $urls): array
    {
        $byUuid = collect($urls)->mapWithKeys(fn (string $url) => [
            (preg_match('#/([0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12})/[^/]+$#i', $url, $m) ? $m[1] : $url) => $url,
        ]);
        $media = Media::query()->whereIn('uuid', $byUuid->keys())->get()->keyBy('uuid');
        $service = app(MediaUrlService::class);

        return $byUuid->map(function (string $url, string $uuid) use ($media, $service) {
            $item = $media->get($uuid);

            return $item
                ? ['src' => $service->url($item, 'medium'), 'srcset' => T::srcset($item), 'thumb' => $service->url($item, 'thumb')]
                : ['src' => $url, 'srcset' => null, 'thumb' => $url];
        })->values()->all();
    }

    private static function host(?string $url): ?string
    {
        $host = $url ? strtolower((string) parse_url($url, PHP_URL_HOST)) : '';

        return $host !== '' ? preg_replace('/^www\./', '', $host) : null;
    }
}
