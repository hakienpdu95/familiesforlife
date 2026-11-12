<?php

namespace Modules\Ocop\Features\Traceability;

use App\Services\Media\MediaUrlService;
use App\Shared\Traceability\Concerns\BuildsRelatedCards;
use App\Shared\Traceability\Contracts\ExposesPublicDocuments;
use App\Shared\Traceability\Contracts\TracePresenter;
use App\Shared\Traceability\TraceabilityService as T;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Number;
use Illuminate\Support\Str;
use Modules\Heritage\Models\HeritageSite;
use Modules\Ocop\Enums\OcopProductStatus;
use Modules\Ocop\Models\OcopProduct;
use Modules\OcopSubject\Models\OcopSubject;

class OcopProductTracePresenter implements ExposesPublicDocuments, TracePresenter
{
    use BuildsRelatedCards;

    private const QUALITY_DOCS = [
        'ocop_label_docs' => ['Nhãn mác, bao bì', 'Mẫu nhãn và quy cách bao bì đang lưu hành'],
        'ocop_quality_declaration' => ['Hồ sơ công bố chất lượng', 'Bản tự công bố, tiêu chuẩn áp dụng (TCCS, TCVN, QCVN)'],
        'ocop_test_reports' => ['Phiếu kiểm nghiệm định kỳ', 'Kết quả kiểm nghiệm chỉ tiêu an toàn thực phẩm'],
        'ocop_quality_certs' => ['Chứng nhận quản lý chất lượng', 'Cơ sở đủ điều kiện ATTP, ISO, HACCP, GMP…'],
    ];

    private const REQUIRED_FROM_STARS = ['ocop_quality_certs' => 4];

    private const REFRESH_MONTHS = ['ocop_test_reports' => 12];

    private const SECTIONS = [
        'ingredients' => ['Thành phần', 'fa-list-check'],
        'usage_instructions' => ['Hướng dẫn sử dụng', 'fa-utensils'],
        'storage_instructions' => ['Hướng dẫn bảo quản', 'fa-boxes-stacked'],
    ];

    public function publicDocumentCollections(): array
    {
        return array_keys(self::QUALITY_DOCS);
    }

    public function view(): string
    {
        return 'ocop::traceability.product';
    }

    public function constrain(Builder $query): void
    {
        $query->withTrashed()->with([
            'ocopSubject' => fn ($q) => $q->withTrashed()->with('qrIdentity'),
            'category:id,name,code,parent_id',
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
            'description' => self::plain($target->description),
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
            'quality_docs' => $this->docStatus($target, array_map(fn (array $d) => $d[0], self::QUALITY_DOCS)),
            'image' => T::imageRef($target, OcopProduct::IMAGE_COLLECTION, 'medium'),
        ];
    }

    /** @param  OcopProduct|null  $live */
    public function page(array $payload, ?Model $live): array
    {
        $subject = is_array($payload['subject'] ?? null) ? $payload['subject'] : ['name' => $payload['subject'] ?? null];
        $payload['quality_docs'] = array_map(fn ($d) => is_array($d) ? [...$d, 'label' => __($d['label'] ?? '')] : ['label' => __($d), 'count' => 1, 'updated_at' => null], $payload['quality_docs'] ?? []);
        if ($live) {
            $payload['quality_docs'] = $this->publicDocs($live);
        }
        $stars = $payload['stars'] ?? null;
        $expiry = ! empty($subject['cert_expiry']) ? Carbon::parse($subject['cert_expiry']) : null;

        $sections = [];
        if ($live && T::hasContent($live->story)) {
            $sections[] = ['key' => 'story', 'title' => __('Câu chuyện sản phẩm'), 'icon' => 'fa-book-open', 'html' => $live->story, 'preview' => Str::limit(OcopProduct::plainText($live->story), 90) ?: null];
        }
        foreach (self::SECTIONS as $field => [$title, $icon]) {
            $html = $live ? OcopProduct::richText($live->{$field}) : null;
            $text = $payload[$field] ?? null;
            $sections[] = [
                'key' => $field,
                'title' => __($title),
                'icon' => $icon,
                'html' => $html && T::hasContent($html) ? $html : null,
                'text' => $html ? null : $text,
                'preview' => Str::limit(OcopProduct::plainText($html ?? $text), 90) ?: null,
                'open' => $field === 'ingredients',
            ];
        }
        $description = $live ? OcopProduct::richText($live->description) : null;

        $related = [];
        if ($live) {
            $live->loadMissing(['articles.translations', 'articles.media']);
            $heritage = $live->heritage_site_id ? HeritageSite::with('media')->whereKey($live->heritage_site_id)->get() : collect();
            $siblings = $live->ocop_subject_id
                ? OcopProduct::query()->with('media')->published()->where('ocop_subject_id', $live->ocop_subject_id)->whereKeyNot($live->id)->latest()->limit(8)->get()
                : collect();
            $related = [
                ['title' => __('Di tích, làng nghề gắn với sản phẩm'), 'icon' => 'fa-landmark', 'items' => $this->heritageCards($heritage)],
                ['title' => __('Bài viết nhắc tới sản phẩm'), 'icon' => 'fa-newspaper', 'items' => $this->articleCards($live->articles)],
                ['title' => __('Sản phẩm khác cùng chủ thể'), 'icon' => 'fa-store', 'items' => $this->ocopCards($siblings)],
            ];
        }

        $channels = $live ? $this->buyChannels($live) : [];
        $detail = $live
            ? ['url' => route('ocop.public.show', ['slug' => $live->slug, 'id' => $live->id]), 'label' => __('Xem chi tiết'), 'icon' => 'fa-arrow-right']
            : ['url' => route('ocop.public.index'), 'label' => __('Xem sản phẩm OCOP khác'), 'icon' => 'fa-arrow-right'];

        return $this->base([
            'title' => $payload['name'] ?? __('Sản phẩm OCOP'),
            'description' => $payload['description'] ?? null,
            'subtitle' => ! empty($subject['name']) ? __('Chủ thể: :name', ['name' => $subject['name']]) : null,
            'images' => $live ? ($this->mediaUrls($live, OcopProduct::IMAGE_COLLECTION) ?: array_filter([$payload['image']['url'] ?? null])) : array_filter([$payload['image']['url'] ?? null]),
            'image_srcsets' => $live ? $this->mediaSrcsets($live, OcopProduct::IMAGE_COLLECTION) : [],
            'gallery' => $live ? $this->gallery($live) : array_map(fn ($url) => ['src' => $url, 'srcset' => null, 'zoom' => $url], array_values(array_filter([$payload['image']['url'] ?? null]))),
            'description_html' => $description && T::hasContent($description) ? $description : null,
            'product_code' => $live ? 'OP'.$live->id : null,
            'updated_label' => format_date($live?->updated_at),
            'info' => $this->info($payload, $live, $sections, $description),
            'badges' => array_values(array_filter([
                ['label' => $stars ? __('OCOP :count sao', ['count' => $stars]) : __('Chưa xếp hạng OCOP'), 'icon' => 'fa-star', 'class' => $stars ? 'badge-warning' : 'badge-ghost'],
                ! empty($payload['category']) ? ['label' => $payload['category'], 'icon' => 'fa-layer-group', 'class' => 'badge-outline'] : null,
            ])),
            'stars' => $stars,
            'category' => $payload['category'] ?? null,
            'category_code' => $live?->category?->fullCode(),
            'area' => $payload['area'] ?? null,
            'facts' => [
                ['icon' => 'fa-hourglass-half', 'label' => __('Hạn sử dụng'), 'value' => self::shelfLife($payload['shelf_life'] ?? null)],
                T::fact('fa-industry', __('Nhà sản xuất'), $payload['producer'] ?? null),
            ],
            'subject' => [
                ...$subject,
                'cert_expiry_label' => format_date($expiry),
                'cert_valid' => $expiry ? $expiry->endOfDay()->isFuture() : null,
                'url' => $this->subjectUrl($live?->ocopSubject),
            ],
            'quality_docs' => $payload['quality_docs'] ?? [],
            'sections' => $sections,
            'related' => $related,
            'channels' => $channels,
            'cta' => $channels ? ['url' => '#mua-hang', 'label' => __('Mua sản phẩm chính hãng')] : ['url' => $detail['url'], 'label' => $live ? __('Xem trang sản phẩm') : $detail['label']],
        ]);
    }

    /** @param  OcopProduct  $target */
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
            'heading' => __('Truy xuất sản phẩm OCOP'),
            'icon' => 'fa-certificate',
            'kind' => __('Sản phẩm OCOP'),
            'stars' => null,
            'subject' => [],
            'category' => null,
            'category_code' => null,
            'area' => null,
            'channels' => [],
            'quality_docs' => [],
            'description' => null,
            'description_html' => null,
            'gallery' => [],
            'product_code' => null,
            'updated_label' => null,
            'info' => null,
            'cta' => ['url' => route('ocop.public.index'), 'label' => __('Xem sản phẩm OCOP khác'), 'icon' => 'fa-arrow-right'],
            ...$page,
        ]);
    }

    private function subjectUrl(?OcopSubject $subject): ?string
    {
        if (! $subject || $subject->trashed()) {
            return null;
        }

        return $subject->qrIdentity?->publicUrl()
            ?? ($subject->is_active ? route('ocop-subject.public.show', $subject->slug) : null);
    }

    private function buyChannels(OcopProduct $product): array
    {
        $channels = [
            ['url' => $product->shopee_url, 'name' => 'Shopee', 'short' => 'Shopee', 'logo' => 'images/shopee.png', 'domains' => ['shopee.vn', 'shp.ee'], 'button' => 'bg-[#ee4d2d] hover:bg-[#d73211]'],
            ['url' => $product->tiktok_url, 'name' => 'TikTok Shop', 'short' => 'TikTok', 'logo' => 'images/tiktok.png', 'domains' => ['tiktok.com'], 'button' => 'bg-[#161823] hover:bg-black'],
        ];

        return array_values(array_filter(array_map(function (array $c) {
            $host = $c['url'] ? strtolower((string) parse_url($c['url'], PHP_URL_HOST)) : '';

            if ($host === '') {
                return null;
            }

            $official = collect($c['domains'])->contains(fn (string $d) => $host === $d || str_ends_with($host, '.'.$d));

            return [...$c, 'host' => preg_replace('/^www\./', '', $host), 'official' => $official, 'logo' => asset($c['logo'])];
        }, $channels)));
    }

    private function info(array $payload, ?OcopProduct $live, array $sections, ?string $description): array
    {
        $producer = $live ? $live->producer_name : ($payload['producer'] ?? null);
        $producerAddress = $live?->producer_address;

        $evidence = function (string $collection) use ($live): ?array {
            $file = $live?->getMedia($collection)
                ->filter(fn ($m) => in_array($m->mime_type, T::DOCUMENT_MIMES, true))
                ->sortByDesc(fn ($m) => $m->created_at)->first();

            return $file ? ['type' => 'doc', 'label' => __('Đối chiếu :doc', ['doc' => mb_strtolower(__(self::QUALITY_DOCS[$collection][0]))]), 'uuid' => $file->uuid] : null;
        };
        $label = $evidence('ocop_label_docs');
        $selfDeclared = ['type' => 'self', 'label' => __('Tự khai báo')];
        $subject = $live?->ocopSubject;
        $subjectLinked = $subject && ! $subject->trashed() && $producer && Str::lower(trim($producer)) === Str::lower(trim((string) $subject->name));

        $row = fn (string $label, ?string $value, array $extra = []) => ['label' => $label, 'value' => filled($value) ? $value : null, ...$extra];

        $groups = [
            ['key' => 'nguon-goc', 'title' => __('Định danh & nguồn gốc'), 'rows' => array_values(array_filter([
                $live ? $row(__('Mã sản phẩm'), 'OP'.$live->id, ['mono' => true, 'required' => true, 'verified' => true]) : null,
                $row(__('Cơ sở sản xuất'), $producer, ['note' => $producerAddress, 'required' => true, 'verified' => $subjectLinked, 'source' => $subjectLinked ? null : $selfDeclared]),
                $row(__('Hạn sử dụng'), self::shelfLife($payload['shelf_life'] ?? null), ['required' => true, 'source' => $label ?? $selfDeclared]),
            ]))],
        ];

        $sources = [
            'ingredients' => $label ?? $selfDeclared,
            'usage_instructions' => $label ?? $selfDeclared,
            'storage_instructions' => $label ?? $selfDeclared,
        ];
        $byKey = collect($sections)->keyBy('key')->map(fn ($s, $k) => isset($sources[$k]) ? [...$s, 'source' => $sources[$k]] : $s);
        $panels = [
            ['key' => 'su-dung', 'title' => __('Thành phần & sử dụng'), 'items' => array_values(array_filter([
                $byKey->get('ingredients'), $byKey->get('usage_instructions'), $byKey->get('storage_instructions'),
            ]))],
            ['key' => 'gioi-thieu', 'title' => __('Giới thiệu'), 'items' => array_values(array_filter([
                $description && T::hasContent($description) && mb_strlen(OcopProduct::plainText($description)) > 220
                    ? ['key' => 'mo-ta', 'title' => __('Mô tả sản phẩm'), 'html' => $description, 'text' => null, 'preview' => null, 'open' => false]
                    : null,
                $byKey->get('story'),
            ]))],
        ];

        $checked = collect($groups)->flatMap(fn ($g) => $g['rows'])->filter(fn ($r) => ! empty($r['required']))
            ->map(fn ($r) => ['label' => $r['label'], 'ok' => filled($r['value']), 'verified' => filled($r['value']) && (! empty($r['verified']) || ($r['source']['type'] ?? null) === 'doc')])
            ->concat(collect(['ingredients', 'usage_instructions', 'storage_instructions'])->map(function ($k) use ($byKey) {
                $ok = filled($byKey->get($k)['html'] ?? null) || filled($byKey->get($k)['text'] ?? null);

                return ['label' => __(self::SECTIONS[$k][0]), 'ok' => $ok, 'verified' => $ok && ($byKey->get($k)['source']['type'] ?? null) === 'doc'];
            }));

        return [
            'groups' => $groups,
            'panels' => array_values(array_filter($panels, fn ($p) => $p['items'])),
            'filled' => $checked->where('ok', true)->count(),
            'verified' => $checked->where('verified', true)->count(),
            'total' => $checked->count(),
            'missing' => $checked->where('ok', false)->pluck('label')->values()->all(),
        ];
    }

    private function gallery(OcopProduct $product): array
    {
        $urls = app(MediaUrlService::class);

        return $product->getMedia(OcopProduct::IMAGE_COLLECTION)
            ->map(fn ($m) => ['src' => $urls->url($m, 'medium'), 'srcset' => T::srcset($m), 'thumb' => $urls->url($m, 'thumb') ?: null, 'zoom' => $urls->url($m) ?: $urls->url($m, 'medium')])
            ->filter(fn ($g) => filled($g['src']))
            ->values()->all();
    }

    private function publicDocs(OcopProduct $product): array
    {
        return collect(self::QUALITY_DOCS)->map(function (array $doc, string $collection) use ($product) {
            $media = $product->getMedia($collection)->sortByDesc(fn ($m) => $m->created_at)->values();
            $latest = $media->first()?->created_at;
            $minStars = self::REQUIRED_FROM_STARS[$collection] ?? null;
            $refresh = self::REFRESH_MONTHS[$collection] ?? null;

            return [
                'label' => __($doc[0]),
                'hint' => __($doc[1]),
                'required' => $minStars && (int) $product->star_rating >= $minStars ? __('Bắt buộc với OCOP :stars–5 sao', ['stars' => $minStars]) : null,
                'count' => $media->count(),
                'updated_at' => format_date($latest),
                'stale' => $refresh && $latest && $latest->lt(now()->subMonths($refresh)) ? __('Tệp mới nhất đăng hơn :count tháng trước — hồ sơ định kỳ có thể đã hết hạn', ['count' => $refresh]) : null,
                'files' => $media->filter(fn ($m) => in_array($m->mime_type, T::DOCUMENT_MIMES, true))
                    ->map(fn ($m) => [
                        'uuid' => $m->uuid,
                        'ref' => strtoupper(substr(str_replace('-', '', $m->uuid), 0, 8)),
                        'type' => $m->mime_type === 'application/pdf' ? 'PDF' : 'Ảnh',
                        'size' => Number::fileSize((int) $m->size, precision: $m->size >= 1048576 ? 1 : 0),
                        'uploaded_at' => format_date($m->created_at),
                    ])->values()->all(),
            ];
        })->values()->all();
    }

    private static function plain(?string $html): ?string
    {
        $text = preg_replace('#<br\s*/?>|</(p|li|div|h[1-6])>#i', "\n", (string) $html);
        $text = trim(preg_replace("/\n{3,}/", "\n\n", html_entity_decode(strip_tags($text))));

        return $text !== '' ? $text : null;
    }

    private static function shelfLife(?string $value): ?string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        return preg_match('/sản xuất|nsx/iu', $value) ? $value : __(':value kể từ ngày sản xuất', ['value' => $value]);
    }
}
