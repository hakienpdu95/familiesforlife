<?php

namespace Modules\VideoIdeaExtractor\Providers;

use Nwidart\Modules\Support\ModuleServiceProvider;

class VideoIdeaExtractorServiceProvider extends ModuleServiceProvider
{
    protected string $name      = 'VideoIdeaExtractor';
    protected string $nameLower = 'videoideaextractor';

    protected array $providers = [
        RouteServiceProvider::class,
    ];

    public function register(): void
    {
        parent::register();

        // nwidart đăng ký config module dưới key lồng `videoideaextractor.video_idea_extractor` —
        // expose lại ở key top-level `video_idea_extractor` để code đọc gọn (cùng pattern
        // CoreIdeaExtractorServiceProvider).
        $this->mergeConfigFrom(
            __DIR__.'/../../config/video_idea_extractor.php',
            'video_idea_extractor'
        );
    }
}
