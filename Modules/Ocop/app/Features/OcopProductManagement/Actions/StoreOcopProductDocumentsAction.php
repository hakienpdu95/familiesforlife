<?php

namespace Modules\Ocop\Features\OcopProductManagement\Actions;

use App\Services\Media\MediaUploadService;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Ocop\Models\OcopProduct;

/**
 * Lưu tài liệu hồ sơ (nhãn mác/bao bì, công bố chất lượng, kiểm nghiệm, chứng nhận) gửi qua
 * FilePond (upload chunk, lưu tạm ở FilePondDraft) — mỗi loại là 1 collection Media riêng (OcopProduct::DOCUMENT_COLLECTIONS).
 */
class StoreOcopProductDocumentsAction
{
    use AsAction;

    public function __construct(private readonly MediaUploadService $mediaUpload) {}

    /** @param  array<string, string[]>  $documents */
    public function handle(OcopProduct $product, array $documents, int $userId): void
    {
        foreach ($documents as $collection => $uuids) {
            if (! in_array($collection, OcopProduct::DOCUMENT_COLLECTIONS, true)) {
                continue;
            }

            $this->mediaUpload->attachFilePondDrafts($product, array_values(array_unique($uuids)), $collection, $userId);
        }
    }
}
