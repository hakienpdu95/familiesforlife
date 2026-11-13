<?php

namespace Modules\OcopSubject\Features\Traceability;

use App\Shared\Traceability\Concerns\BuildsRelatedCards;
use App\Shared\Traceability\Contracts\TracePresenter;
use App\Shared\Traceability\TraceabilityService as T;
use App\Support\I18n\PortalLocale;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Modules\Ocop\Models\OcopProduct;
use Modules\OcopSubject\Models\OcopSubject;

/**
 * Hồ sơ doanh nghiệp công khai — KHÔNG đưa tax_code (có thể là số CCCD) và legal_representative
 * vào snapshot lẫn trang hiển thị.
 */
class OcopSubjectTracePresenter implements TracePresenter
{
    use BuildsRelatedCards;

    public function view(): string
    {
        return 'ocopsubject::traceability.subject';
    }

    public function constrain(Builder $query): void
    {
        $query->withTrashed()->with('media');
    }

    /** @param  OcopSubject  $target */
    public function isPublic(Model $target): bool
    {
        return (bool) $target->is_active;
    }

    /** @param  OcopSubject  $target */
    public function snapshot(Model $target): array
    {
        $story = trim(preg_replace('/\s+/u', ' ', strip_tags((string) $target->story)));

        return [
            'name' => $target->originalAttribute('name'),
            'name_translations' => collect(PortalLocale::prefixed())
                ->mapWithKeys(fn (string $locale) => [$locale => $target->translationFor($locale)?->name])
                ->filter()
                ->all(),
            'type' => $target->organization_type?->label(),
            'stars' => $target->ocop_star,
            'cert_expiry' => $target->ocop_cert_expiry?->toDateString(),
            'is_food_business' => (bool) $target->is_food_business,
            'address' => $target->fullAddress() ?: null,
            'gps' => self::gps($target->gps_coordinates),
            'hotline' => $target->hotline,
            'email' => $target->email,
            'website' => $target->website,
            'story' => $story !== '' ? Str::limit($story, 600) : null,
            'docs' => $this->docStatus($target, OcopSubject::DOCUMENT_LABELS),
            'products_count' => $target->products()->published()->count(),
            'image' => T::imageRef($target, OcopSubject::IMAGE_COLLECTION, 'medium'),
        ];
    }

    /** @param  OcopSubject|null  $live */
    public function page(array $payload, ?Model $live): array
    {
        $expiry = ! empty($payload['cert_expiry']) ? Carbon::parse($payload['cert_expiry']) : null;
        $gps = $payload['gps'] ?? null;
        $stars = $payload['stars'] ?? null;

        $sections = [];
        if ($live && T::hasContent($live->story)) {
            $sections[] = ['title' => __('Câu chuyện thương hiệu'), 'icon' => 'fa-book-open', 'html' => $live->story];
        } elseif (! empty($payload['story'])) {
            $sections[] = ['title' => __('Câu chuyện thương hiệu'), 'icon' => 'fa-book-open', 'text' => $payload['story']];
        }

        $products = $live
            ? OcopProduct::query()->with(['media', 'qrIdentity'])->published()->where('ocop_subject_id', $live->id)->orderByDesc('star_rating')->latest()->limit(12)->get()
            : collect();

        [$title, $subtitle] = self::localizedName($payload);

        return $this->base([
            'title' => $title,
            'subtitle' => $subtitle,
            'images' => $live ? ($this->mediaUrls($live, OcopSubject::IMAGE_COLLECTION) ?: array_filter([$payload['image']['url'] ?? null])) : array_filter([$payload['image']['url'] ?? null]),
            'image_srcsets' => $live ? $this->mediaSrcsets($live, OcopSubject::IMAGE_COLLECTION) : [],
            'badges' => array_values(array_filter([
                ! empty($payload['type']) ? ['label' => $payload['type'], 'icon' => 'fa-building', 'class' => 'badge-outline'] : null,
                $stars ? ['label' => 'OCOP '.$stars.' sao', 'icon' => 'fa-star', 'class' => 'badge-warning'] : null,
                ! empty($payload['is_food_business']) ? ['label' => __('Cơ sở thực phẩm'), 'icon' => 'fa-utensils', 'class' => 'badge-ghost'] : null,
            ])),
            'stars' => $stars,
            'type' => $payload['type'] ?? null,
            'products_count' => $payload['products_count'] ?? null,
            'is_food_business' => ! empty($payload['is_food_business']),
            'cert' => $expiry ? ['label' => format_date($expiry), 'valid' => $expiry->endOfDay()->isFuture()] : null,
            'facts' => [
                T::fact('fa-building', __('Loại hình'), $payload['type'] ?? null),
                T::fact('fa-star', __('Hạng OCOP cao nhất'), $stars ? __(':count sao', ['count' => $stars]) : null),
                T::fact('fa-boxes-stacked', __('Sản phẩm OCOP'), isset($payload['products_count']) ? __(':count sản phẩm', ['count' => $payload['products_count']]) : null),
                T::fact('fa-calendar-check', __('Hiệu lực chứng nhận'), format_date($expiry)),
            ],
            'contact' => [
                'address' => $payload['address'] ?? null,
                'hotline' => $payload['hotline'] ?? null,
                'email' => $payload['email'] ?? null,
                'website' => $payload['website'] ?? null,
                'map_url' => $gps ? 'https://www.google.com/maps/dir/?api=1&destination='.$gps : null,
            ],
            'docs' => array_map(fn ($d) => is_array($d) ? [...$d, 'label' => __($d['label'] ?? '')] : $d, $payload['docs'] ?? []),
            'sections' => $sections,
            'related' => [
                ['title' => __('Sản phẩm OCOP của chủ thể'), 'icon' => 'fa-certificate', 'items' => $this->ocopCards($products, linkToQr: true)],
            ],
            'cta' => $live
                ? ['url' => route('ocop-subject.public.show', $live->slug), 'label' => __('Trang thương hiệu'), 'icon' => 'fa-store']
                : ['url' => route('ocop.public.index'), 'label' => __('Xem sản phẩm OCOP'), 'icon' => 'fa-arrow-right'],
        ]);
    }

    /** @param  OcopSubject  $target */
    public function pending(Model $target): array
    {
        return $this->base([
            'title' => $target->name,
            'notice' => __('Hồ sơ chủ thể đang được cập nhật.'),
        ]);
    }

    private function base(array $page): array
    {
        return T::page([
            'heading' => __('Hồ sơ chủ thể OCOP'),
            'icon' => 'fa-building',
            'kind' => __('Chủ thể OCOP'),
            'stars' => null,
            'type' => null,
            'products_count' => null,
            'is_food_business' => false,
            'cert' => null,
            'contact' => [],
            'docs' => [],
            'cta' => ['url' => route('ocop.public.index'), 'label' => __('Xem sản phẩm OCOP'), 'icon' => 'fa-arrow-right'],
            ...$page,
        ]);
    }

    private static function gps(?string $raw): ?string
    {
        if (! $raw || ! preg_match('/(-?\d{1,3}\.\d+)\s*[,;\s]\s*(-?\d{1,3}\.\d+)/', $raw, $m)) {
            return null;
        }

        return $m[1].','.$m[2];
    }

    /** @return array{0: string, 1: ?string} */
    private static function localizedName(array $payload): array
    {
        $original = $payload['name'] ?? __('Chủ thể OCOP');
        $translations = (array) ($payload['name_translations'] ?? []);
        foreach ($payload as $key => $value) {
            if (is_string($key) && preg_match('/^name_([a-z]{2})$/', $key, $m) && filled($value)) {
                $translations[$m[1]] ??= $value;
            }
        }
        $translated = $translations[app()->getLocale()] ?? null;

        if (filled($translated)) {
            return [$translated, $original];
        }

        return [$original, collect($translations)->first(fn ($name) => filled($name) && $name !== $original)];
    }
}
