<?php

namespace Modules\Heritage\Features\HeritageSiteManagement\Actions;

use App\Models\Province;
use App\Models\Ward;
use Illuminate\Validation\ValidationException;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Heritage\Features\HeritageSiteManagement\Data\HeritageSiteData;
use Modules\Heritage\Models\HeritageSite;

class UpdateHeritageSiteAction
{
    use AsAction;

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

        return $site;
    }
}
