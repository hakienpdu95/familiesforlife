<?php

namespace Modules\Ocop\Features\OcopProductManagement\Actions;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Laravel\Facades\Image;
use Lorisleiva\Actions\Concerns\AsAction;

/**
 * spec/Province_Showcase_Technical_Specification.md §4.2 — copy nguyên pattern
 * Modules\Banner\Features\BannerManagement\Actions\StoreBannerImageAction (Intervention Image
 * v4, decodePath()->scaleDown()->encode(), resize nếu > config('ocop.max_image_width')) —
 * KHÔNG dùng Spatie MediaLibrary, tránh 2 cách quản lý ảnh song song trong cùng codebase.
 */
class StoreOcopProductImageAction
{
    use AsAction;

    /** @return array{image_path: string, image_width: ?int, image_height: ?int, image_size_bytes: int} */
    public function handle(UploadedFile $file): array
    {
        $maxWidth = (int) config('ocop.max_image_width', 1200);
        $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();
        $path     = 'ocop/' . $filename;

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

        $storedPath = $file->storeAs('ocop', $filename, 'public');

        return [
            'image_path'       => $storedPath,
            'image_width'      => $width,
            'image_height'     => $height,
            'image_size_bytes' => $file->getSize(),
        ];
    }
}
