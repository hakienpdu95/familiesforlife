<?php

namespace Modules\Ocop\Features\OcopProductManagement\Actions;

use App\Services\Media\MediaUploadService;
use Illuminate\Http\UploadedFile;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Ocop\Models\OcopProduct;

/**
 * Lưu tài liệu hồ sơ (nhãn mác/bao bì, công bố chất lượng, kiểm nghiệm, chứng nhận) gửi qua
 * <input type="file" multiple> — mỗi loại là 1 collection Media riêng (OcopProduct::DOCUMENT_COLLECTIONS).
 */
class StoreOcopProductDocumentsAction
{
    use AsAction;

    public function __construct(private readonly MediaUploadService $mediaUpload) {}

    /** @param  array<string, UploadedFile[]>  $documents */
    public function handle(OcopProduct $product, array $documents): void
    {
        foreach ($documents as $collection => $files) {
            if (! in_array($collection, OcopProduct::DOCUMENT_COLLECTIONS, true)) {
                continue;
            }

            foreach ($files as $file) {
                $this->mediaUpload->upload($file, $product, $collection);
            }
        }
    }
}
