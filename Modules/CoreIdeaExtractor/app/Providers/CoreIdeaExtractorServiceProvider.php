<?php

namespace Modules\CoreIdeaExtractor\Providers;

use Nwidart\Modules\Support\ModuleServiceProvider;

/**
 * spec/CoreIdeaExtractor.md §12 — Category Content Foundation (model + Gate quản lý theo category)
 * đã tách sang module dùng chung Modules\ContentFoundation
 * (Modules\ContentFoundation\Providers\ContentFoundationServiceProvider::boot()). Module này giờ
 * chỉ còn Layer 1/2 (trích xuất dữ liệu thô + sinh ý tưởng từ URL bài viết), không có Eloquent
 * Model nào của riêng nó.
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
