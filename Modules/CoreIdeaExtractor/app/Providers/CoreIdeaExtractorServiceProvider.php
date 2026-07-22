<?php

namespace Modules\CoreIdeaExtractor\Providers;

use Nwidart\Modules\Support\ModuleServiceProvider;

/**
 * Module không có Eloquent Model nào (stateless — chỉ fetch + parse HTML, không persist) nên
 * không có Gate::policy() nào để đăng ký ở đây (khác Banner/Newsletter). Quyền truy cập gate
 * qua middleware 'can:core_idea_extractor.use' trực tiếp trên route (xem routes/web.php) —
 * Spatie Permission tự đăng ký permission string này làm Gate ability, không cần Policy class.
 */
class CoreIdeaExtractorServiceProvider extends ModuleServiceProvider
{
    protected string $name      = 'CoreIdeaExtractor';
    protected string $nameLower = 'coreideaextractor';

    protected array $providers = [
        RouteServiceProvider::class,
    ];

    public function register(): void
    {
        parent::register();

        // nwidart đăng ký config module dưới key lồng `coreideaextractor.core_idea_extractor`
        // (xem Modules/WorkflowAutomation/app/Providers/WorkflowAutomationServiceProvider.php
        // — cùng pattern) — expose lại ở key top-level `core_idea_extractor` để code đọc gọn
        // (`config('core_idea_extractor.confidence...')`).
        $this->mergeConfigFrom(
            __DIR__.'/../../config/core_idea_extractor.php',
            'core_idea_extractor'
        );
    }
}
