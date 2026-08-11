<?php

namespace Modules\Ocop\Features\OcopProductManagement\Actions;

use App\Models\Province;
use App\Models\Ward;
use App\Services\Media\MediaUploadService;
use Illuminate\Support\Str;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Ocop\Features\OcopProductManagement\Data\OcopProductData;
use Modules\Ocop\Models\OcopProduct;

/**
 * spec/Province_Showcase_Technical_Specification.md §3.2.1 (nguyên tắc chung, áp dụng cả OCOP) —
 * cùng BuildEventAttributesAction: LUÔN tra lại tên thật từ bảng provinces/wards ở tầng Action,
 * không tin tên gửi từ client (form chỉ gửi province_code/ward_code qua <x-address-picker>).
 */
class CreateOcopProductAction
{
    use AsAction;

    public function __construct(private readonly MediaUploadService $mediaUpload) {}

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
        if ($data->cover_media_uuid) {
            $this->mediaUpload->reassociateFilePondDrafts($product, [$data->cover_media_uuid], 'cover');
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
