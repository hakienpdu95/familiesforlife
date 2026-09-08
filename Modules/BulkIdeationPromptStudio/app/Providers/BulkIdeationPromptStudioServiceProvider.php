<?php

namespace Modules\BulkIdeationPromptStudio\Providers;

use Nwidart\Modules\Support\ModuleServiceProvider;

class BulkIdeationPromptStudioServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'BulkIdeationPromptStudio';

    protected string $nameLower = 'bulkideationpromptstudio';

    protected array $providers = [
        RouteServiceProvider::class,
    ];

    public function register(): void
    {
        parent::register();

        // nwidart đăng ký config module dưới key lồng `bulkideationpromptstudio.bulk_ideation_prompt_studio`
        // — expose lại ở key top-level `bulk_ideation_prompt_studio` để code đọc gọn (cùng pattern
        // VideoSeriesPromptStudioServiceProvider).
        $this->mergeConfigFrom(
            __DIR__.'/../../config/bulk_ideation_prompt_studio.php',
            'bulk_ideation_prompt_studio'
        );
    }
}
