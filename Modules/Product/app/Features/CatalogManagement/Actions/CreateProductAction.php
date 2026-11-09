<?php

namespace Modules\Product\Features\CatalogManagement\Actions;

use Illuminate\Support\Str;
use App\Services\Media\MediaUploadService;
use App\Services\Media\MediaUrlService;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Approval\Actions\SubmitForApprovalAction;
use Modules\Product\Features\CatalogManagement\Data\ProductData;
use Modules\Product\Models\Product;
use Modules\Post\Support\ArticleContentRenderer;

class CreateProductAction
{
    use AsAction;

    public function __construct(
        private readonly MediaUploadService $mediaUpload,
        private readonly MediaUrlService $mediaUrl,
        private readonly ArticleContentRenderer $renderer,
    ) {}

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
            'description'            => $this->renderer->sanitizeTextHtml($data->description) ?: null,
            'content'                => $this->renderer->sanitizeTextHtml($data->content) ?: null,
            'price'                  => $data->price,
            'price_label'            => $data->price_label,
            'currency'               => $data->currency,
            'cover_image_url'        => null,
            'status'                 => $data->status,
            'shopee_url'             => $data->shopee_url,
            'tiktok_url'             => $data->tiktok_url,
            'supplier_url'           => $data->supplier_url,
            'supplier_homepage_url'  => $data->supplier_homepage_url,
            'is_featured'            => $data->is_featured,
            'sort_order'             => $data->sort_order,
            'created_by'             => auth()->id(),
        ]);

        if ($data->media_uuids) {
            $this->mediaUpload->attachFilePondDrafts($product, $data->media_uuids, Product::GALLERY, (int) auth()->id());
            if ($first = $product->load('media')->getFirstMedia(Product::GALLERY)) {
                $product->forceFill(['cover_image_url' => $this->mediaUrl->url($first)])->saveQuietly();
            }
        }

        // Cùng UpdateOcopProductAction — ảnh chèn qua Jodit sống tạm ở JoditDraft cho tới khi lưu,
        // "nhận" vào sản phẩm thật và dọn ảnh không còn trong nội dung (không nhận sẽ bị
        // media:cleanup-orphans xoá sau 24h). saveQuietly — không kích hoạt lại HasApproval.
        $this->mediaUpload->reassociateOrphans($product, $product->contentMediaUuids());

        $content = $this->mediaUrl->refreshEmbeddedImageUrls($product->content);
        if ($content !== $product->content) {
            $product->forceFill(['content' => $content])->saveQuietly();
        }

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
