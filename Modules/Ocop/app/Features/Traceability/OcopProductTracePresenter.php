<?php

namespace Modules\Ocop\Features\Traceability;

use App\Shared\Traceability\Concerns\BuildsRelatedCards;
use App\Shared\Traceability\Contracts\TracePresenter;
use App\Shared\Traceability\TraceabilityService as T;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Modules\Heritage\Models\HeritageSite;
use Modules\Ocop\Enums\OcopProductStatus;
use Modules\Ocop\Models\OcopProduct;

class OcopProductTracePresenter implements TracePresenter
{
    use BuildsRelatedCards;

    private const QUALITY_DOCS = [
        'ocop_label_docs' => 'Nhãn mác, bao bì',
        'ocop_quality_declaration' => 'Hồ sơ công bố chất lượng',
        'ocop_test_reports' => 'Phiếu kiểm nghiệm định kỳ',
        'ocop_quality_certs' => 'Chứng nhận quản lý chất lượng',
    ];

    private const SECTIONS = [
        'ingredients' => ['Thành phần', 'fa-list-check'],
        'usage_instructions' => ['Hướng dẫn sử dụng', 'fa-utensils'],
        'storage_instructions' => ['Hướng dẫn bảo quản', 'fa-boxes-stacked'],
    ];

    public function view(): string
    {
        return 'ocop::traceability.product';
    }

    public function constrain(Builder $query): void
    {
        $query->withTrashed()->with([
            'ocopSubject' => fn ($q) => $q->withTrashed(),
            'category:id,name',
            'media',
        ]);
    }

    /** @param  OcopProduct  $target */
    public function isPublic(Model $target): bool
    {
        return $target->status === OcopProductStatus::Published;
    }

    /** @param  OcopProduct  $target */
    public function snapshot(Model $target): array
    {
        $subject = $target->ocopSubject;

        return [
            'name' => $target->name,
            'category' => $target->category?->name,
            'stars' => $target->star_rating,
            'subject' => [
                'name' => $subject?->name ?: $target->producer_name,
                'type' => $subject?->organization_type?->label(),
                'stars' => $subject?->ocop_star,
                'cert_expiry' => $subject?->ocop_cert_expiry?->toDateString(),
                'hotline' => $subject?->hotline,
                'website' => $subject?->website,
                'address' => $subject?->address ?: $target->producer_address,
            ],
            'producer' => $target->producer_name && $target->producer_name !== $subject?->name ? $target->producer_name : null,
            'origin' => $target->origin,
            'area' => implode(', ', array_filter([$target->ward_name, $target->province_name])) ?: null,
            'production_date' => $target->production_date,
            'shelf_life' => $target->shelf_life,
            'ingredients' => self::plain($target->ingredients),
            'usage_instructions' => self::plain($target->usage_instructions),
            'storage_instructions' => self::plain($target->storage_instructions),
            'quality_docs' => $this->docStatus($target, self::QUALITY_DOCS),
            'image' => T::imageRef($target, OcopProduct::IMAGE_COLLECTION),
        ];
    }

    /** @param  OcopProduct|null  $live */
    public function page(array $payload, ?Model $live): array
    {
        $subject = is_array($payload['subject'] ?? null) ? $payload['subject'] : ['name' => $payload['subject'] ?? null];
        $payload['quality_docs'] = array_map(fn ($d) => is_array($d) ? $d : ['label' => $d, 'count' => 1, 'updated_at' => null], $payload['quality_docs'] ?? []);
        $stars = $payload['stars'] ?? null;
        $expiry = ! empty($subject['cert_expiry']) ? Carbon::parse($subject['cert_expiry']) : null;

        $sections = [];
        if ($live && filled($live->story)) {
            $sections[] = ['title' => 'Câu chuyện sản phẩm', 'icon' => 'fa-book-open', 'html' => $live->story];
        }
        foreach (self::SECTIONS as $field => [$title, $icon]) {
            if (filled($payload[$field] ?? null)) {
                $sections[] = ['title' => $title, 'icon' => $icon, 'text' => $payload[$field]];
            }
        }

        $related = [];
        if ($live) {
            $live->loadMissing(['articles.translations', 'articles.media']);
            $heritage = $live->heritage_site_id ? HeritageSite::with('media')->whereKey($live->heritage_site_id)->get() : collect();
            $siblings = $live->ocop_subject_id
                ? OcopProduct::query()->with('media')->published()->where('ocop_subject_id', $live->ocop_subject_id)->whereKeyNot($live->id)->latest()->limit(8)->get()
                : collect();
            $related = [
                ['title' => 'Di tích, làng nghề gắn với sản phẩm', 'icon' => 'fa-landmark', 'items' => $this->heritageCards($heritage)],
                ['title' => 'Bài viết nhắc tới sản phẩm', 'icon' => 'fa-newspaper', 'items' => $this->articleCards($live->articles)],
                ['title' => 'Sản phẩm khác cùng chủ thể', 'icon' => 'fa-store', 'items' => $this->ocopCards($siblings)],
            ];
        }

        $channels = $live ? $this->buyChannels($live) : [];
        $detail = $live
            ? ['url' => route('ocop.public.show', ['slug' => $live->slug, 'id' => $live->id]), 'label' => 'Xem chi tiết', 'icon' => 'fa-arrow-right']
            : ['url' => route('ocop.public.index'), 'label' => 'Xem sản phẩm OCOP khác', 'icon' => 'fa-arrow-right'];

        return $this->base([
            'title' => $payload['name'] ?? 'Sản phẩm OCOP',
            'subtitle' => ! empty($subject['name']) ? 'Chủ thể: '.$subject['name'] : null,
            'images' => $live ? ($this->mediaUrls($live, OcopProduct::IMAGE_COLLECTION) ?: array_filter([$payload['image']['url'] ?? null])) : array_filter([$payload['image']['url'] ?? null]),
            'badges' => array_values(array_filter([
                ['label' => $stars ? 'OCOP '.$stars.' sao' : 'Chưa xếp hạng OCOP', 'icon' => 'fa-star', 'class' => $stars ? 'badge-warning' : 'badge-ghost'],
                ! empty($payload['category']) ? ['label' => $payload['category'], 'icon' => 'fa-layer-group', 'class' => 'badge-outline'] : null,
            ])),
            'stars' => $stars,
            'facts' => [
                T::fact('fa-seedling', 'Xuất xứ', $payload['origin'] ?? null),
                T::fact('fa-location-dot', 'Địa bàn', $payload['area'] ?? null),
                T::fact('fa-calendar', 'Ngày sản xuất', $payload['production_date'] ?? null),
                T::fact('fa-hourglass-half', 'Hạn sử dụng', $payload['shelf_life'] ?? null),
                T::fact('fa-industry', 'Nhà sản xuất', $payload['producer'] ?? null),
                T::fact('fa-layer-group', 'Ngành hàng', $payload['category'] ?? null),
            ],
            'subject' => [
                ...$subject,
                'cert_expiry_label' => $expiry?->format('d/m/Y'),
                'cert_valid' => $expiry ? $expiry->endOfDay()->isFuture() : null,
                'url' => $live?->ocopSubject && $live->ocopSubject->is_active && ! $live->ocopSubject->trashed()
                    ? route('ocop-subject.public.show', $live->ocopSubject->slug) : null,
            ],
            'quality_docs' => $payload['quality_docs'] ?? [],
            'sections' => $sections,
            'related' => $related,
            'channels' => $channels,
            'cta' => $channels ? ['url' => '#mua-hang', 'label' => 'Mua chính hãng', 'icon' => 'fa-bag-shopping'] : $detail,
            'cta_secondary' => $channels ? $detail : null,
        ]);
    }

    /** @param  OcopProduct  $target */
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
            'heading' => 'Truy xuất sản phẩm OCOP',
            'icon' => 'fa-certificate',
            'kind' => 'Sản phẩm OCOP',
            'stars' => null,
            'subject' => [],
            'quality_docs' => [],
            'archive_text' => 'Thông tin dưới đây là bản lưu trữ. Sản phẩm này hiện đã ngừng kinh doanh hoặc bị gỡ khỏi hệ thống.',
            'cta' => ['url' => route('ocop.public.index'), 'label' => 'Xem sản phẩm OCOP khác', 'icon' => 'fa-arrow-right'],
            ...$page,
        ]);
    }

    private function buyChannels(OcopProduct $product): array
    {
        $channels = [
            ['url' => $product->shopee_url, 'name' => 'Shopee', 'icon' => 'fa-solid fa-bag-shopping', 'tone' => 'bg-[#ee4d2d] text-white'],
            ['url' => $product->tiktok_url, 'name' => 'TikTok Shop', 'icon' => 'fa-brands fa-tiktok', 'tone' => 'bg-black text-white'],
        ];

        return array_values(array_filter(array_map(function (array $c) {
            $host = $c['url'] ? parse_url($c['url'], PHP_URL_HOST) : null;

            return $host ? [...$c, 'host' => preg_replace('/^www\./', '', $host)] : null;
        }, $channels)));
    }

    private static function plain(?string $html): ?string
    {
        $text = trim(strip_tags((string) $html));

        return $text !== '' ? $text : null;
    }
}
