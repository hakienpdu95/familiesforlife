<?php

namespace Modules\Product\Features\CatalogManagement\Actions;

use Illuminate\Support\Str;
use App\Models\Media;
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
            'description'            => $data->description,
            'content'                => $this->renderer->sanitizeTextHtml($data->content) ?: null,
            'price'                  => $data->price,
            'price_label'            => $data->price_label,
            'currency'               => $data->currency,
            'cover_image_url'        => $data->cover_media_uuid ? null : $data->cover_image_url,
            'status'                 => $data->status,
            'shopee_url'             => $data->shopee_url,
            'tiktok_url'             => $data->tiktok_url,
            'supplier_url'           => $data->supplier_url,
            'supplier_homepage_url'  => $data->supplier_homepage_url,
            'is_featured'            => $data->is_featured,
            'sort_order'             => $data->sort_order,
            'created_by'             => auth()->id(),
        ]);

        // Ảnh đại diện upload qua FilePond (FilePondDraft) — "nhận" vào sản phẩm rồi ghi URL vào
        // cover_image_url (cột mọi nơi khác đang đọc: CTA box, picker…). saveQuietly — trước
        // SubmitForApprovalAction bên dưới nên snapshot gửi duyệt đã có ảnh.
        if ($data->cover_media_uuid && ($coverUrl = $this->attachCover($product, $data->cover_media_uuid))) {
            $product->forceFill(['cover_image_url' => $coverUrl])->saveQuietly();
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

    private function attachCover(Product $product, string $uuid): ?string
    {
        $this->mediaUpload->reassociateFilePondDrafts($product, [$uuid], 'cover');
        // Query trực tiếp cùng điều kiện reassociateFilePondDrafts — không qua $product->getMedia()
        // (relation media có thể đã nạp trước khi reassociate, trả rỗng).
        $media = Media::withoutTenant()
            ->where('model_type', $product::class)
            ->where('model_id', $product->getKey())
            ->where('collection_name', 'cover')
            ->latest('id')
            ->first();

        return $media ? $this->mediaUrl->url($media) : null;
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
