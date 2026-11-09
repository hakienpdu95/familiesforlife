<?php

namespace Modules\Product\Features\CatalogManagement\Actions;

use App\Models\Media;
use App\Services\Media\MediaUploadService;
use App\Services\Media\MediaUrlService;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Approval\Actions\ReviseContentAction;
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
        $gallery = $this->syncGallery($product, $data);
        $coverBefore = $product->cover_image_url;

        $product->update([
            'cover_image_url'        => $gallery['cover'],
            'category_id'            => $data->category_id,
            'name'                   => $data->name,
            'sku'                    => $data->sku,
            'type'                   => $data->type,
            'short_description'      => $data->short_description,
            'description'            => $this->renderer->sanitizeTextHtml($data->description) ?: null,
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

        if ($gallery['changed'] && $coverBefore === $product->cover_image_url) {
            app(ReviseContentAction::class)->handle($product);
        }

        return $product;
    }

    /** @return array{cover: ?string, changed: bool} */
    private function syncGallery(Product $product, ProductData $data): array
    {
        if ($product->isApprovalArchived()) {
            return ['cover' => $product->cover_image_url, 'changed' => false];
        }

        $changed = false;

        if ($data->remove_media_uuids) {
            $product->getMedia(Product::GALLERY)
                ->whereIn('uuid', $data->remove_media_uuids)
                ->each(function (Media $media) use (&$changed) {
                    $this->mediaUpload->delete($media);
                    $changed = true;
                });
        }

        if ($data->media_uuids && $this->mediaUpload->attachFilePondDrafts($product, $data->media_uuids, Product::GALLERY, (int) auth()->id()) > 0) {
            $changed = true;
        }

        $first = $product->load('media')->getFirstMedia(Product::GALLERY);

        return [
            'cover' => match (true) {
                $first !== null => $this->mediaUrl->url($first),
                $changed, $data->remove_cover => null,
                default => $product->cover_image_url,
            },
            'changed' => $changed,
        ];
    }
}
