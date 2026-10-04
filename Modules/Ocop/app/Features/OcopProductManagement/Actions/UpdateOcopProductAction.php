<?php

namespace Modules\Ocop\Features\OcopProductManagement\Actions;

use App\Services\Media\MediaUploadService;
use App\Services\Media\MediaUrlService;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Ocop\Features\OcopProductManagement\Data\OcopProductData;
use Modules\Ocop\Models\OcopProduct;
use Modules\OcopSubject\Models\OcopSubject;
use Modules\Post\Support\ArticleContentRenderer;

class UpdateOcopProductAction
{
    use AsAction;

    public function __construct(
        private readonly MediaUploadService $mediaUpload,
        private readonly MediaUrlService $mediaUrl,
        private readonly ArticleContentRenderer $renderer,
    ) {}

    public function handle(OcopProduct $product, OcopProductData $data): OcopProduct
    {
        // Ảnh mới KHÔNG cần xử lý ở đây — form sửa gắn thẳng qua FilePond context header
        // (X-Context-Type=ocop_product, X-Context-Id=$product->id), upload đi thẳng vào Media
        // của chính sản phẩm này (spec §8). Chỉ xoá ảnh cũ editor đã đánh dấu.
        $product->update([
            'category_id' => $data->category_id,
            'name' => $data->name,
            'star_rating' => $data->star_rating,
            'description' => $data->description,
            'story' => $this->renderer->sanitizeTextHtml($data->story) ?: null,
            'origin' => $data->origin,
            'production_date' => $data->production_date,
            'shelf_life' => $data->shelf_life,
            'ingredients' => $data->ingredients,
            'usage_instructions' => $data->usage_instructions,
            'storage_instructions' => $data->storage_instructions,
            ...SyncOcopSubjectSnapshotAction::attributesFor(OcopSubject::findOrFail($data->ocop_subject_id)),
            'heritage_site_id' => $data->heritage_site_id,
            'purchase_url' => $data->purchase_url,
            'status' => $data->status,
            'is_featured' => $data->is_featured,
            'sort_order' => $data->sort_order,
            'updated_by' => auth()->id(),
        ]);

        // Cùng UpdateTranslationAction (Post) — ảnh chèn qua Jodit sống tạm ở JoditDraft cho tới
        // khi lưu, "nhận" vào sản phẩm thật và dọn ảnh không còn trong nội dung.
        $this->mediaUpload->reassociateOrphans($product, $product->storyMediaUuids());

        $story = $this->mediaUrl->refreshEmbeddedImageUrls($product->story);
        if ($story !== $product->story) {
            $product->forceFill(['story' => $story])->saveQuietly();
        }

        if (! empty($data->remove_media_uuids)) {
            $product->media()
                ->whereIn('collection_name', [OcopProduct::IMAGE_COLLECTION, ...OcopProduct::DOCUMENT_COLLECTIONS])
                ->whereIn('uuid', $data->remove_media_uuids)
                ->get()
                ->each(fn ($media) => $this->mediaUpload->delete($media));
        }

        return $product;
    }
}
