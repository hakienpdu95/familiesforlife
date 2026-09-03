<?php

namespace Modules\VideoSeriesPromptStudio\Providers;

use Nwidart\Modules\Support\ModuleServiceProvider;

class VideoSeriesPromptStudioServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'VideoSeriesPromptStudio';

    protected string $nameLower = 'videoseriespromptstudio';

    protected array $providers = [
        RouteServiceProvider::class,
    ];

    public function register(): void
    {
        parent::register();

        // nwidart đăng ký config module dưới key lồng `videoseriespromptstudio.video_series_prompt_studio`
        // — expose lại ở key top-level `video_series_prompt_studio` để code đọc gọn (cùng pattern
        // VideoIdeaExtractorServiceProvider).
        $this->mergeConfigFrom(
            __DIR__.'/../../config/video_series_prompt_studio.php',
            'video_series_prompt_studio'
        );
    }
}
