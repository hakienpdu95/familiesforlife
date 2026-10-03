<?php

namespace Modules\Ocop\Features\OcopProductManagement\Actions;

use App\Models\Province;
use App\Models\Ward;
use App\Services\Media\MediaUploadService;
use App\Services\Media\MediaUrlService;
use Illuminate\Support\Str;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Ocop\Features\OcopProductManagement\Data\OcopProductData;
use Modules\Ocop\Models\OcopProduct;
use Modules\Post\Support\ArticleContentRenderer;

/**
 * spec/Province_Showcase_Technical_Specification.md §3.2.1 (nguyên tắc chung, áp dụng cả OCOP) —
 * cùng BuildEventAttributesAction: LUÔN tra lại tên thật từ bảng provinces/wards ở tầng Action,
 * không tin tên gửi từ client (form chỉ gửi province_code/ward_code qua <x-address-picker>).
 */
class CreateOcopProductAction
{
    use AsAction;

    public function __construct(
        private readonly MediaUploadService $mediaUpload,
        private readonly MediaUrlService $mediaUrl,
        private readonly ArticleContentRenderer $renderer,
    ) {}

    public function handle(OcopProductData $data): OcopProduct
    {
        $provinceName = $data->province_code
            ? Province::where('province_code', $data->province_code)->value('name')
            : null;
        $wardName = $data->ward_code
            ? Ward::where('ward_code', $data->ward_code)->value('name')
            : null;

        $product = OcopProduct::create([
            'category_id' => $data->category_id,
            'name' => $data->name,
            'slug' => $this->uniqueSlug($data->name),
            'star_rating' => $data->star_rating,
            'description' => $data->description,
            'story' => $this->renderer->sanitizeTextHtml($data->story) ?: null,
            'origin' => $data->origin,
            'production_date' => $data->production_date,
            'shelf_life' => $data->shelf_life,
            'ingredients' => $data->ingredients,
            'usage_instructions' => $data->usage_instructions,
            'storage_instructions' => $data->storage_instructions,
            'province_code' => $data->province_code,
            'province_name' => $provinceName,
            'ward_code' => $data->ward_code,
            'ward_name' => $wardName,
            'producer_name' => $data->producer_name,
            'producer_address' => $data->producer_address,
            'heritage_site_id' => $data->heritage_site_id,
            'purchase_url' => $data->purchase_url,
            'status' => $data->status,
            'is_featured' => $data->is_featured,
            'sort_order' => $data->sort_order,
            'created_by' => auth()->id(),
        ]);

        // spec/Media_Library_Technical_Specification.md §8 — form tạo mới chưa có product.id
        // lúc FilePond upload, ảnh tạm gắn ở FilePondDraft — "nhận" vào sản phẩm thật vừa tạo.
        if (! empty($data->media_uuids)) {
            $this->mediaUpload->reassociateFilePondDrafts($product, $data->media_uuids, OcopProduct::IMAGE_COLLECTION);
        }

        // Cùng UpdateTranslationAction (Post) — ảnh chèn qua Jodit sống tạm ở JoditDraft cho tới
        // khi lưu, "nhận" vào sản phẩm thật và dọn ảnh không còn trong nội dung.
        $this->mediaUpload->reassociateOrphans($product, $product->storyMediaUuids());

        $story = $this->mediaUrl->refreshEmbeddedImageUrls($product->story);
        if ($story !== $product->story) {
            $product->forceFill(['story' => $story])->saveQuietly();
        }

        return $product;
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $i = 2;

        while (OcopProduct::where('slug', $slug)->exists()) {
            $slug = "{$base}-{$i}";
            $i++;
        }

        return $slug;
    }
}
