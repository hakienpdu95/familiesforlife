<?php

namespace Modules\Heritage\Features\Traceability;

use App\Shared\Traceability\Concerns\BuildsRelatedCards;
use App\Shared\Traceability\Contracts\TracePresenter;
use App\Shared\Traceability\TraceabilityService as T;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Modules\Heritage\Enums\HeritageSiteStatus;
use Modules\Heritage\Enums\HeritageVisitingStatus;
use Modules\Heritage\Models\HeritageSite;
use Modules\Ocop\Models\OcopProduct;

class HeritageSiteTracePresenter implements TracePresenter
{
    use BuildsRelatedCards;

    private const INTRO_CHARS = 600;

    private const RANK_CLASS = [
        'special_national' => 'badge-error',
        'national' => 'badge-primary',
        'provincial' => 'badge-info',
        'unranked' => 'badge-ghost',
    ];

    public function view(): string
    {
        return 'heritage::traceability.site';
    }

    public function constrain(Builder $query): void
    {
        $query->withTrashed()->with('media');
    }

    /** @param  HeritageSite  $target */
    public function isPublic(Model $target): bool
    {
        return $target->status === HeritageSiteStatus::Published;
    }

    /** @param  HeritageSite  $target */
    public function snapshot(Model $target): array
    {
        $intro = trim(preg_replace('/\s+/u', ' ', strip_tags((string) $target->description)));

        return [
            'name' => $target->name,
            'type' => $target->heritage_type?->label(),
            'rank' => $target->rank?->label(),
            'rank_value' => $target->rank?->value,
            'era' => $target->era,
            'address' => implode(', ', array_filter([$target->address, $target->ward_name, $target->province_name])) ?: null,
            'latitude' => $target->latitude !== null ? (float) $target->latitude : null,
            'longitude' => $target->longitude !== null ? (float) $target->longitude : null,
            'intro' => $intro !== '' ? Str::limit($intro, self::INTRO_CHARS) : null,
            'created_at' => $target->created_at?->toDateString(),
            'image' => T::imageRef($target, 'cover'),
        ];
    }

    /** @param  HeritageSite|null  $live */
    public function page(array $payload, ?Model $live): array
    {
        $lat = $payload['latitude'] ?? null;
        $lng = $payload['longitude'] ?? null;
        $visiting = $live ? $this->visiting($live) : null;

        $sections = [];
        $intro = $live ? (filled($live->description) ? trim(strip_tags($live->description)) : null) : ($payload['intro'] ?? null);
        if ($intro) {
            $sections[] = ['title' => 'Giới thiệu', 'icon' => 'fa-book-open', 'text' => $intro];
        }
        if ($live && filled($live->content)) {
            $sections[] = ['title' => 'Lịch sử & giá trị di tích', 'icon' => 'fa-landmark', 'html' => $live->content];
        }

        $related = [];
        if ($live) {
            $live->loadMissing(['articles.translations', 'articles.media']);
            $products = OcopProduct::query()->with('media')->published()->where('heritage_site_id', $live->id)->latest()->limit(8)->get();
            $related = [
                ['title' => 'Sản phẩm OCOP của làng nghề / di tích', 'icon' => 'fa-certificate', 'items' => $this->ocopCards($products)],
                ['title' => 'Bài viết về di tích', 'icon' => 'fa-newspaper', 'items' => $this->articleCards($live->articles)],
            ];
        }

        return $this->base([
            'title' => $payload['name'] ?? 'Di tích',
            'subtitle' => $payload['era'] ?? null,
            'images' => array_filter([$payload['image']['url'] ?? null]),
            'badges' => array_values(array_filter([
                ! empty($payload['rank']) ? ['label' => $payload['rank'], 'icon' => 'fa-award', 'class' => self::RANK_CLASS[$payload['rank_value'] ?? ''] ?? 'badge-primary'] : null,
                ! empty($payload['type']) ? ['label' => $payload['type'], 'icon' => 'fa-landmark', 'class' => 'badge-outline'] : null,
            ])),
            'facts' => [
                T::fact('fa-award', 'Cấp xếp hạng', $payload['rank'] ?? null),
                T::fact('fa-landmark', 'Loại hình', $payload['type'] ?? null),
                T::fact('fa-clock', 'Niên đại', $payload['era'] ?? null),
                T::fact('fa-door-open', 'Tham quan', $visiting['label'] ?? null),
            ],
            'visiting' => $visiting,
            'address' => $payload['address'] ?? null,
            'map_url' => $lat && $lng ? 'https://www.google.com/maps/dir/?api=1&destination='.$lat.','.$lng : null,
            'coordinates' => $lat && $lng ? number_format($lat, 5).', '.number_format($lng, 5) : null,
            'sections' => $sections,
            'timeline' => T::timeline([
                T::event(! empty($payload['created_at']) ? Carbon::parse($payload['created_at']) : null, 'Di tích được số hoá trên hệ thống', null, 'primary'),
                T::event($live?->updated_at, 'Thông tin cập nhật lần cuối', null, 'success'),
            ]),
            'related' => $related,
            'cta' => $live
                ? ['url' => route('heritage.public.show', ['slug' => $live->slug, 'id' => $live->id]), 'label' => 'Trang giới thiệu', 'icon' => 'fa-book-open']
                : ['url' => route('heritage.public.index'), 'label' => 'Khám phá di sản khác', 'icon' => 'fa-arrow-right'],
            'cta_secondary' => $live && $lat && $lng
                ? ['url' => 'https://www.google.com/maps/dir/?api=1&destination='.$lat.','.$lng, 'label' => 'Chỉ đường', 'icon' => 'fa-route', 'external' => true]
                : null,
        ]);
    }

    /** @param  HeritageSite  $target */
    public function pending(Model $target): array
    {
        return $this->base([
            'title' => $target->name,
            'notice' => 'Thông tin chi tiết đang được cập nhật.',
        ]);
    }

    private function visiting(HeritageSite $site): ?array
    {
        if (! $site->visiting_status || $site->visiting_status === HeritageVisitingStatus::Unknown) {
            return null;
        }

        return [
            'label' => $site->visiting_status->label(),
            'tone' => match ($site->visiting_status) {
                HeritageVisitingStatus::Open => 'success',
                HeritageVisitingStatus::Restoring => 'warning',
                default => 'error',
            },
        ];
    }

    private function base(array $page): array
    {
        return T::page([
            'heading' => 'Thông tin di tích',
            'icon' => 'fa-landmark',
            'kind' => 'Di tích, di sản',
            'visiting' => null,
            'address' => null,
            'map_url' => null,
            'coordinates' => null,
            'archive_text' => 'Thông tin dưới đây là bản lưu trữ. Di tích này hiện đã bị gỡ khỏi hệ thống.',
            'cta' => ['url' => route('heritage.public.index'), 'label' => 'Khám phá di sản khác', 'icon' => 'fa-arrow-right'],
            ...$page,
        ]);
    }
}
