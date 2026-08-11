<?php

namespace Modules\Heritage\Features\HeritageSiteManagement\Actions;

use App\Models\Province;
use App\Models\Ward;
use App\Services\Media\MediaUploadService;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Heritage\Features\HeritageSiteManagement\Data\HeritageSiteData;
use Modules\Heritage\Models\HeritageSite;

/**
 * spec/Heritage_Technical_Specification.md §3.5 — LUÔN tra lại tên thật từ bảng provinces/wards
 * ở tầng Action, không tin tên gửi từ client (cùng nguyên tắc CreateOcopProductAction).
 */
class CreateHeritageSiteAction
{
    use AsAction;

    public function __construct(private readonly MediaUploadService $mediaUpload) {}

    public function handle(HeritageSiteData $data): HeritageSite
    {
        // §5.1 — latitude/longitude bắt buộc đi cùng nhau (cả hai cùng có hoặc cùng trống); tự
        // kiểm tra đơn giản ở đây thay vì rule required_with hai chiều (dễ gây lỗi lặp/khó hiểu).
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

        $site = HeritageSite::create([
            'name' => $data->name,
            // §3.5 — chỉ tự sinh khi form để trống slug; nếu biên tập viên tự nhập, giá trị đã
            // qua validate unique:heritage_sites,slug ở controller, dùng nguyên.
            'slug' => $data->slug ?: $this->generateUniqueSlug($data->name),
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
            'created_by' => auth()->id(),
        ]);

        // spec/Media_Library_Technical_Specification.md §8 — form tạo mới chưa có site.id lúc
        // FilePond upload, ảnh tạm gắn ở FilePondDraft — "nhận" vào di tích vừa tạo.
        if ($data->cover_media_uuid) {
            $this->mediaUpload->reassociateFilePondDrafts($site, [$data->cover_media_uuid], 'cover');
        }

        return $site;
    }

    /**
     * spec/Heritage_Technical_Specification.md §3.5 — slug unique TOÀN CỤC (không composite theo
     * tỉnh): tên di tích trùng lặp giữa các tỉnh rất phổ biến trong tiếng Việt, và URL công khai
     * ({slug}-ds{id}.html) không mang theo province_code nên composite (slug, province_code)
     * không giải quyết được va chạm route thật.
     */
    private function generateUniqueSlug(string $name): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $suffix = 2;

        while (HeritageSite::where('slug', $slug)->exists()) {
            $slug = "{$base}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }
}
