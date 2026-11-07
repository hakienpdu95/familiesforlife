<?php

namespace Modules\Ocop\Features\Traceability;

use App\Shared\Traceability\Contracts\TracePresenter;
use App\Shared\Traceability\TraceabilityService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Modules\Ocop\Enums\OcopProductStatus;
use Modules\Ocop\Models\OcopProduct;

class OcopProductTracePresenter implements TracePresenter
{
    private const QUALITY_DOCS = [
        'ocop_quality_declaration' => 'Hồ sơ công bố chất lượng',
        'ocop_test_reports' => 'Phiếu kiểm nghiệm định kỳ',
        'ocop_quality_certs' => 'Chứng nhận quản lý chất lượng',
    ];

    private const SECTIONS = [
        'ingredients' => 'Thành phần',
        'usage_instructions' => 'Hướng dẫn sử dụng',
        'storage_instructions' => 'Hướng dẫn bảo quản',
    ];

    public function view(): string
    {
        return 'ocop::traceability.product';
    }

    public function constrain(Builder $query): void
    {
        $query->withTrashed()->with([
            'ocopSubject' => fn ($q) => $q->withTrashed()->select('id', 'name', 'deleted_at'),
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
        $subject = $target->ocopSubject?->name ?: $target->producer_name;

        return [
            'name' => $target->name,
            'category' => $target->category?->name,
            'stars' => $target->star_rating,
            'subject' => $subject,
            'producer' => $target->producer_name && $target->producer_name !== $subject ? $target->producer_name : null,
            'producer_address' => $target->producer_address,
            'origin' => $target->origin,
            'area' => implode(', ', array_filter([$target->ward_name, $target->province_name])) ?: null,
            'production_date' => $target->production_date,
            'shelf_life' => $target->shelf_life,
            'ingredients' => self::plain($target->ingredients),
            'usage_instructions' => self::plain($target->usage_instructions),
            'storage_instructions' => self::plain($target->storage_instructions),
            'quality_docs' => array_values(array_filter(
                self::QUALITY_DOCS,
                fn (string $label, string $collection) => $target->getMedia($collection)->isNotEmpty(),
                ARRAY_FILTER_USE_BOTH,
            )),
            'image' => TraceabilityService::imageRef($target, OcopProduct::IMAGE_COLLECTION),
        ];
    }

    /** @param  OcopProduct|null  $live */
    public function page(array $payload, ?Model $live): array
    {
        $sections = [];
        foreach (self::SECTIONS as $field => $title) {
            if (filled($payload[$field] ?? null)) {
                $sections[] = ['title' => $title, 'text' => $payload[$field]];
            }
        }

        return $this->base([
            'title' => $payload['name'] ?? 'Sản phẩm OCOP',
            'image' => $payload['image']['url'] ?? null,
            'stars' => $payload['stars'] ?? null,
            'category' => $payload['category'] ?? null,
            'subject' => $payload['subject'] ?? null,
            'producer' => $payload['producer'] ?? null,
            'producer_address' => $payload['producer_address'] ?? null,
            'quality_docs' => $payload['quality_docs'] ?? [],
            'facts' => array_values(array_filter([
                ['Xuất xứ', ($payload['origin'] ?? null) ?: 'Đang cập nhật'],
                ! empty($payload['area']) ? ['Địa bàn', $payload['area']] : null,
                ! empty($payload['production_date']) ? ['Ngày sản xuất', $payload['production_date']] : null,
                ['Hạn sử dụng', ($payload['shelf_life'] ?? null) ?: 'Đang cập nhật'],
            ])),
            'sections' => $sections,
            'description' => $live && filled($live->description) ? trim(strip_tags($live->description)) : null,
            'content_html' => $live && filled($live->story) ? $live->story : null,
            'cta' => $live
                ? ['url' => route('ocop.public.show', ['slug' => $live->slug, 'id' => $live->id]), 'label' => 'Xem chi tiết sản phẩm OCOP']
                : ['url' => route('ocop.public.index'), 'label' => 'Xem sản phẩm OCOP khác'],
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
        return TraceabilityService::page([
            'heading' => 'Truy xuất sản phẩm OCOP',
            'icon' => 'fa-certificate',
            'stars' => null,
            'category' => null,
            'subject' => null,
            'producer' => null,
            'producer_address' => null,
            'quality_docs' => [],
            'description' => null,
            'content_html' => null,
            'archive_text' => 'Cảnh báo: Thông tin dưới đây là bản lưu trữ. Sản phẩm này hiện đã ngừng kinh doanh hoặc bị gỡ khỏi hệ thống.',
            'cta' => ['url' => route('ocop.public.index'), 'label' => 'Xem sản phẩm OCOP khác'],
            ...$page,
        ]);
    }

    private static function plain(?string $html): ?string
    {
        $text = trim(strip_tags((string) $html));

        return $text !== '' ? $text : null;
    }
}
