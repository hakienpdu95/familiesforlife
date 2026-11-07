<?php

namespace App\Console\Commands;

use App\Models\Media;
use App\Services\Media\MediaUploadService;
use Illuminate\Console\Command;

class MediaGenerateConversionsCommand extends Command
{
    protected $signature = 'media:generate-conversions
                            {conversion : Tên conversion trong config media.conversion_settings}
                            {--force : Tạo lại cả những ảnh đã có conversion này}';

    protected $description = 'Tạo conversion còn thiếu cho media đã upload, theo các collection khai báo conversion đó trong config/media.php';

    public function handle(MediaUploadService $uploads): int
    {
        $conversion = (string) $this->argument('conversion');

        if (! config("media.conversion_settings.{$conversion}")) {
            $this->error("Conversion '{$conversion}' không có trong media.conversion_settings.");

            return self::FAILURE;
        }

        $collections = collect(config('media.collections', []))
            ->filter(fn (array $c) => in_array($conversion, $c['conversions'] ?? [], true))
            ->keys()
            ->all();

        if ($collections === []) {
            $this->warn("Không collection nào khai báo conversion '{$conversion}'.");

            return self::SUCCESS;
        }

        $query = Media::query()->whereIn('collection_name', $collections)->where('mime_type', 'like', 'image/%');
        $done = 0;
        $skipped = 0;

        $bar = $this->output->createProgressBar((clone $query)->count());

        $query->chunkById(100, function ($items) use ($uploads, $conversion, &$done, &$skipped, $bar) {
            foreach ($items as $media) {
                $bar->advance();

                if (! $this->option('force') && array_key_exists($conversion, $media->generated_conversions ?? [])) {
                    $skipped++;

                    continue;
                }

                $uploads->generateConversions($media, [$conversion]);
                $done++;
            }
        });

        $bar->finish();
        $this->newLine();
        $this->info("Đã xử lý {$done} ảnh, bỏ qua {$skipped} ảnh đã có '{$conversion}' (collection: ".implode(', ', $collections).').');

        return self::SUCCESS;
    }
}
