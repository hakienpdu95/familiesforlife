<?php

namespace Modules\ContentOutlines\Providers;

use Nwidart\Modules\Support\ModuleServiceProvider;

/**
 * spec/ContentOutlines_Technical_Specification.md §6 — quyền gate phẳng bằng permission string
 * 'content_outlines.use' đọc trực tiếp ở routes/web.php (middleware 'can:'), KHÔNG cần
 * Gate::define riêng ở đây — cùng nguyên tắc CoreIdeaExtractorServiceProvider.
 */
class ContentOutlinesServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'ContentOutlines';

    protected string $nameLower = 'contentoutlines';

    protected array $providers = [
        RouteServiceProvider::class,
    ];
}
