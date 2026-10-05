<?php

namespace Modules\ProvinceShowcase\Features\ProvinceManagement\Actions;

use App\Models\Province;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\ProvinceShowcase\Features\ProvinceManagement\Data\ProvinceData;

class UpdateProvinceAction
{
    use AsAction;

    public function __construct(
        private readonly StoreProvinceImageAction $storeImage,
    ) {}

    public function handle(Province $province, ProvinceData $data, ?UploadedFile $cover = null, ?UploadedFile $logo = null): Province
    {
        $oldCover = $province->cover_image;
        $oldLogo = $province->logo;
        $newCover = $cover ? $this->storeImage->handle($cover, 'covers') : null;
        $newLogo = $logo ? $this->storeImage->handle($logo, 'logos') : null;

        $province = DB::transaction(function () use ($province, $data, $newCover, $oldCover, $newLogo, $oldLogo) {
            if ($data->is_featured && ! $province->is_featured) {
                $max = (int) config('provinceshowcase.featured_max', 5);

                $featuredCount = Province::query()
                    ->where('is_featured', true)
                    ->whereKeyNot($province->getKey())
                    ->lockForUpdate()
                    ->count();

                if ($featuredCount >= $max) {
                    throw ValidationException::withMessages([
                        'is_featured' => "Chỉ được phép chọn tối đa {$max} tỉnh/thành hiển thị nổi bật.",
                    ]);
                }
            }

            $province->forceFill([
                'short_name' => $data->short_name,
                'order_column' => $data->order_column,
                'is_active' => $data->is_active,
                'is_featured' => $data->is_featured,
                'slogan' => $data->slogan,
                'description' => $data->description,
                'tvc_video_url' => $data->tvc_video_url,
                'vr360_map_url' => $data->vr360_map_url,
                'highlight_tags' => $data->highlight_tags ?: null,
                'cover_image' => $newCover ?? ($data->remove_cover_image ? null : $oldCover),
                'logo' => $newLogo ?? ($data->remove_logo ? null : $oldLogo),
            ])->save();

            return $province;
        });

        if ($oldCover && $oldCover !== $province->cover_image) {
            Storage::disk('public')->delete($oldCover);
        }

        if ($oldLogo && $oldLogo !== $province->logo) {
            Storage::disk('public')->delete($oldLogo);
        }

        return $province;
    }
}
