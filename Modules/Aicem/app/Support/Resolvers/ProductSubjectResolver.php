<?php

namespace Modules\Aicem\Support\Resolvers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Validation\ValidationException;
use Modules\Aicem\Contracts\AicemSubjectResolver;
use Modules\Aicem\Support\PriceTierBucketer;
use Modules\Aicem\Support\Resolvers\Exceptions\AicemSuggestionApplyException;
use Modules\Product\Enums\ProductLinkType;
use Modules\Product\Features\CatalogManagement\Actions\UpdateProductAction;
use Modules\Product\Features\CatalogManagement\Data\ProductData;
use Modules\Product\Models\Product;

/**
 * Adapter cho subject_type=product — xem spec/AICEM_Technical_Specification.md mục 6.2/11.1.
 * `UpdateProductAction::handle()` ghi CẢ 17 cột nguyên khối (không có action update 1 field
 * riêng) nên resolver phải: đọc bản Product TƯƠI (trong transaction đã lockForUpdate của
 * AcceptSuggestionAction — mục 9.1), dựng lại `ProductData` qua constructor (KHÔNG qua
 * `::from()`/`::validateAndCreate()` để tránh vỡ vì field khác không liên quan không hợp lệ),
 * chỉ overlay đúng 1 field được accept.
 */
class ProductSubjectResolver implements AicemSubjectResolver
{
    public function fields(Model $subject): array
    {
        /** @var Product $subject */
        return [
            'name'              => $subject->name,
            'short_description' => $subject->short_description,
            'description'       => $subject->description,
        ];
    }

    public function blocks(Model $subject): array
    {
        return [];
    }

    public function applyFieldSuggestion(Model $subject, string $field, string $suggestedText, int $userId): void
    {
        /** @var Product $product */
        $product = $subject;

        $data = new ProductData(
            name:                   $field === 'name' ? $suggestedText : $product->name,
            type:                   $product->type,
            status:                 $product->status,
            category_id:            $product->category_id,
            sku:                    $product->sku,
            short_description:      $field === 'short_description' ? $suggestedText : $product->short_description,
            description:            $field === 'description' ? $suggestedText : $product->description,
            price:                  $product->price !== null ? (float) $product->price : null,
            price_label:            $product->price_label,
            currency:               $product->currency,
            cover_image_url:        $product->cover_image_url,
            shopee_url:             $product->shopee_url,
            tiktok_url:             $product->tiktok_url,
            supplier_url:           $product->supplier_url,
            supplier_homepage_url:  $product->supplier_homepage_url,
            is_featured:            $product->is_featured,
            sort_order:             $product->sort_order,
        );

        try {
            app(UpdateProductAction::class)->handle($product, $data);
        } catch (ValidationException $e) {
            $firstField = array_key_first($e->errors()) ?? '?';
            throw new AicemSuggestionApplyException(
                "Dữ liệu sản phẩm hiện không hợp lệ ở field \"{$firstField}\" — sửa field đó trước khi áp dụng gợi ý cho \"{$field}\"."
            );
        } catch (QueryException $e) {
            // Trường hợp biên (mục 11.1): 1 field KHÔNG liên quan đang có giá trị vi phạm ràng
            // buộc DB (VD cột varchar bị giới hạn độ dài do dữ liệu cũ/import lỗi) — vì
            // UpdateProductAction ghi lại NGUYÊN KHỐI 17 cột, field đó vẫn bị ghi lại y nguyên và
            // có thể trúng lỗi dù ta chỉ đang muốn đổi field khác. Dịch sang thông báo rõ ràng
            // thay vì để lộ QueryException thô ra UI.
            throw new AicemSuggestionApplyException(
                "Không thể áp dụng gợi ý cho \"{$field}\" — dữ liệu sản phẩm hiện có 1 field khác đang vi phạm ràng buộc dữ liệu "
                . '(VD quá dài so với giới hạn cột). Hãy sửa dữ liệu sản phẩm hiện có trước, sau đó thử lại.'
            );
        }
    }

    public function applyBlockSuggestion(Model $subject, int $blockId, string $suggestedText, int $userId): void
    {
        throw new \LogicException('Product không hỗ trợ block suggestion (has_blocks=false trong registry).');
    }

    public function taxonomy(Model $subject): array
    {
        /** @var Product $subject */
        $subject->loadMissing('category');

        return [
            'category_slugs' => $subject->category ? [$subject->category->slug] : [],
            'price_tier'     => [PriceTierBucketer::bucket($subject->price !== null ? (float) $subject->price : null)],
            'link_types'     => collect(ProductLinkType::cases())
                ->filter(fn ($type) => filled($subject->{$type->urlColumn()}))
                ->map(fn ($type) => $type->value)
                ->values()
                ->all(),
        ];
    }
}
