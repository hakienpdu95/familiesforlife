<?php

namespace Modules\Heritage\Features\HeritageSiteManagement\Actions;

use App\Models\Province;
use App\Models\Ward;
use App\Services\Media\MediaUploadService;
use App\Services\Media\MediaUrlService;
use Illuminate\Validation\ValidationException;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Heritage\Features\HeritageSiteManagement\Data\HeritageSiteData;
use Modules\Heritage\Models\HeritageSite;
use Modules\Post\Support\ArticleContentRenderer;

class UpdateHeritageSiteAction
{
    use AsAction;

    public function __construct(
        private readonly MediaUploadService $mediaUpload,
        private readonly MediaUrlService $mediaUrl,
        private readonly ArticleContentRenderer $renderer,
    ) {}

    public function handle(HeritageSite $site, HeritageSiteData $data): HeritageSite
    {
        // §5.1 — cùng kiểm tra như CreateHeritageSiteAction.
        if (($data->latitude === null) !== ($data->longitude === null)) {
            throw ValidationException::withMessages([
                'latitude' => 'Vĩ độ và kinh độ phải được nhập cùng nhau, hoặc để trống cả hai.',
            ]);
        }

        $provinceName = $data->province_code
            ? Province::where('province_code', $data->province_code)->value('name')
            : null;
        $wardName = $data->ward_code
            ? Ward::where('ward_code', $data->ward_code)->value('name')
            : null;

        // Ảnh KHÔNG cần xử lý ở đây — form sửa gắn thẳng qua FilePond context header
        // (X-Context-Type=heritage_site, X-Context-Id=$site->id), cùng nguyên tắc OcopProduct.
        $site->update([
            'name' => $data->name,
            // §3.5 — đổi slug sau publish là quyết định có ý thức của biên tập viên (không tự
            // sinh lại ở update, khác create) — validate unique đã chạy ở controller.
            'slug' => $data->slug ?: $site->slug,
            'heritage_type' => $data->heritage_type,
            'rank' => $data->rank,
            'era' => $data->era,
            'description' => $data->description,
            'content' => $this->renderer->sanitizeTextHtml($data->content) ?: null,
            'province_code' => $data->province_code,
            'province_name' => $provinceName,
            'ward_code' => $data->ward_code,
            'ward_name' => $wardName,
            'address' => $data->address,
            'latitude' => $data->latitude,
            'longitude' => $data->longitude,
            'visiting_status' => $data->visiting_status,
            'status' => $data->status,
            'is_featured' => $data->is_featured,
            'sort_order' => $data->sort_order,
            'updated_by' => auth()->id(),
        ]);

        // Cùng UpdateOcopProductAction — ảnh chèn qua Jodit sống tạm ở JoditDraft cho tới khi lưu,
        // "nhận" vào di tích thật và dọn ảnh không còn trong nội dung.
        $this->mediaUpload->reassociateOrphans($site, $site->contentMediaUuids());

        $content = $this->mediaUrl->refreshEmbeddedImageUrls($site->content);
        if ($content !== $site->content) {
            $site->forceFill(['content' => $content])->saveQuietly();
        }

        return $site;
    }
}
