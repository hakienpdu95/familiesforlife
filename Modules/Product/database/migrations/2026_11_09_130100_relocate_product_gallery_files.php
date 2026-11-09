<?php

use App\Models\Media;
use App\Services\Media\MediaUrlService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Modules\Product\Models\Product;

return new class extends Migration
{
    public function up(): void
    {
        $alias = (new Product)->getMorphClass();
        $urls = app(MediaUrlService::class);

        Media::query()->where('model_type', $alias)->where('collection_name', Product::GALLERY)->get()
            ->each(function (Media $media) use ($urls) {
                $disk = Storage::disk($media->disk);
                $legacy = 'media/product/product/'.$media->model_id.'/'.$media->uuid;
                $target = rtrim(dirname($media->getPathRelativeToRoot()), '/');

                if ($legacy !== $target && $disk->exists($legacy) && ! $disk->exists($target)) {
                    foreach ($disk->allFiles($legacy) as $file) {
                        $disk->move($file, $target.'/'.basename($file));
                    }
                    $disk->deleteDirectory($legacy);
                }

                $first = Media::query()->where('model_type', $media->model_type)->where('model_id', $media->model_id)
                    ->where('collection_name', Product::GALLERY)->orderBy('order_column')->orderBy('id')->first();

                if ($first) {
                    DB::table('products')->where('id', $media->model_id)->update(['cover_image_url' => $urls->url($first)]);
                }
            });
    }

    public function down(): void {}
};
