<?php

namespace Modules\Product\Features\CatalogManagement\Actions;

use App\Models\Media;
use App\Services\Media\MediaUploadService;
use App\Services\Media\MediaUrlService;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Product\Features\CatalogManagement\Data\ProductData;
use Modules\Product\Models\Product;
use Modules\Post\Support\ArticleContentRenderer;

class UpdateProductAction
{
    use AsAction;

    public function __construct(
        private readonly MediaUploadService $mediaUpload,
        private readonly MediaUrlService $mediaUrl,
        private readonly ArticleContentRenderer $renderer,
    ) {}

    public function handle(Product $product, ProductData $data): Product
    {
        $product->update([
            ...$this->resolveCover($product, $data),
            'category_id'            => $data->category_id,
            'name'                   => $data->name,
            'sku'                    => $data->sku,
            'type'                   => $data->type,
            'short_description'      => $data->short_description,
            'description'            => $data->description,
            'content'                => $this->renderer->sanitizeTextHtml($data->content) ?: null,
            'price'                  => $data->price,
            'price_label'            => $data->price_label,
            'currency'               => $data->currency,
            'status'                 => $data->status,
            'shopee_url'             => $data->shopee_url,
            'tiktok_url'             => $data->tiktok_url,
            'supplier_url'           => $data->supplier_url,
            'supplier_homepage_url'  => $data->supplier_homepage_url,
            'is_featured'            => $data->is_featured,
            'sort_order'             => $data->sort_order,
            'updated_by'             => auth()->id(),
        ]);

        // Cùng UpdateOcopProductAction — ảnh chèn qua Jodit sống tạm ở JoditDraft cho tới khi lưu,
        // "nhận" vào sản phẩm thật và dọn ảnh không còn trong nội dung (không nhận sẽ bị
        // media:cleanup-orphans xoá sau 24h). saveQuietly — không kích hoạt lại HasApproval.
        $this->mediaUpload->reassociateOrphans($product, $product->contentMediaUuids());

        $content = $this->mediaUrl->refreshEmbeddedImageUrls($product->content);
        if ($content !== $product->content) {
            $product->forceFill(['content' => $content])->saveQuietly();
        }

        return $product;
    }

    /**
     * cover_image_url sau khi lưu — ảnh FilePond mới > "Xóa ảnh hiện tại" > giá trị DTO. Ghi qua
     * $product->update() (KHÔNG saveQuietly) để HasApproval thấy cover_image_url đổi → duyệt lại.
     * Ảnh chỉ gắn/xoá khi bấm Lưu (luồng draft) — bấm Hủy thì ảnh cũ còn nguyên.
     *
     * @return array{cover_image_url: ?string}
     */
    private function resolveCover(Product $product, ProductData $data): array
    {
        // Archived: nội dung read-only (HasApproval chặn) — không gắn/xoá media trước khi update()
        // ném InvalidTransitionException, tránh xoá mất file ảnh cũ mà URL vẫn trỏ tới.
        if ($product->isApprovalArchived()) {
            return ['cover_image_url' => $product->cover_image_url];
        }

        if ($data->cover_media_uuid) {
            $this->mediaUpload->reassociateFilePondDrafts($product, [$data->cover_media_uuid], 'cover');
            $media = Media::withoutTenant()
                ->where('model_type', $product::class)
                ->where('model_id', $product->getKey())
                ->where('collection_name', 'cover')
                ->latest('id')
                ->first();

            if ($media) {
                return ['cover_image_url' => $this->mediaUrl->url($media)];
            }
        }

        if ($data->remove_cover) {
            Media::withoutTenant()
                ->where('model_type', $product::class)
                ->where('model_id', $product->getKey())
                ->where('collection_name', 'cover')
                ->get()
                ->each(fn (Media $media) => $this->mediaUpload->delete($media));

            return ['cover_image_url' => null];
        }

        return ['cover_image_url' => $data->cover_image_url];
    }
}
