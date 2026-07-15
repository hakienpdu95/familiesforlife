<?php

namespace Modules\Banner\Features\BannerManagement\Actions;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Laravel\Facades\Image;
use Lorisleiva\Actions\Concerns\AsAction;

/**
 * spec/Banner_Management_Technical_Specification.md §5.1 — cùng khung sườn
 * StoreEventPosterAction (đọc width/height, lưu qua Storage::disk('public')), cộng thêm bước
 * resize nếu ảnh gốc rộng hơn config('banner.max_image_width') — mục đích giảm dung lượng file
 * (banner nặng làm chậm mọi trang có banner đó, kể cả header_ad xuất hiện trên toàn site),
 * không phải yêu cầu nghiệp vụ về kích thước hiển thị. Giữ nguyên định dạng ảnh gốc (không ép
 * chuyển sang WebP như MediaUploadService::runConversions() — banner chỉ 1 bản duy nhất,
 * không có nhiều "conversion" cần tối ưu thêm).
 */
class StoreBannerImageAction
{
    use AsAction;

    /** @return array{image_path: string, image_width: ?int, image_height: ?int, image_size_bytes: int} */
    public function handle(UploadedFile $file): array
    {
        $maxWidth = (int) config('banner.max_image_width', 1200);
        $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();
        $path     = 'banners/' . $filename;

        [$width, $height] = @getimagesize($file->getRealPath()) ?: [null, null];

        if ($width && $width > $maxWidth) {
            $image = Image::decodePath($file->getRealPath())->scaleDown(width: $maxWidth);

            $encoded = $image->encode();

            Storage::disk('public')->put($path, (string) $encoded);

            return [
                'image_path'       => $path,
                'image_width'      => $image->width(),
                'image_height'     => $image->height(),
                'image_size_bytes' => strlen((string) $encoded),
            ];
        }

        $storedPath = $file->storeAs('banners', $filename, 'public');

        return [
            'image_path'       => $storedPath,
            'image_width'      => $width,
            'image_height'     => $height,
            'image_size_bytes' => $file->getSize(),
        ];
    }
}
