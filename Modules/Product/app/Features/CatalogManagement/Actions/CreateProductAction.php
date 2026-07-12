<?php

namespace Modules\Product\Features\CatalogManagement\Actions;

use Illuminate\Support\Str;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Approval\Actions\SubmitForApprovalAction;
use Modules\Product\Features\CatalogManagement\Data\ProductData;
use Modules\Product\Models\Product;

class CreateProductAction
{
    use AsAction;

    /**
     * Platform Approval Gateway (hệ thống nội bộ Hà Kiên) — MỌI sản phẩm mới tạo đều tự động
     * gửi duyệt ngay, không chờ doanh nghiệp bấm "Gửi duyệt" thủ công. Sản phẩm chỉ thật sự
     * hiển thị công khai (isPubliclyVisible()) sau khi đội kiểm duyệt tập trung (content_
     * moderator) Approve + Publish — xem ProductPolicy::approve/publishApproval.
     */
    public function handle(ProductData $data): Product
    {
        $product = Product::create([
            'category_id'            => $data->category_id,
            'name'                   => $data->name,
            'slug'                   => $this->uniqueSlug($data->name),
            'sku'                    => $data->sku,
            'type'                   => $data->type,
            'short_description'      => $data->short_description,
            'description'            => $data->description,
            'price'                  => $data->price,
            'price_label'            => $data->price_label,
            'currency'               => $data->currency,
            'cover_image_url'        => $data->cover_image_url,
            'status'                 => $data->status,
            'shopee_url'             => $data->shopee_url,
            'tiktok_url'             => $data->tiktok_url,
            'supplier_url'           => $data->supplier_url,
            'supplier_homepage_url'  => $data->supplier_homepage_url,
            'is_featured'            => $data->is_featured,
            'sort_order'             => $data->sort_order,
            'created_by'             => auth()->id(),
        ]);

        app(SubmitForApprovalAction::class)->handle($product);

        return $product;
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $i    = 2;

        while (Product::where('slug', $slug)->exists()) {
            $slug = "{$base}-{$i}";
            $i++;
        }

        return $slug;
    }
}
