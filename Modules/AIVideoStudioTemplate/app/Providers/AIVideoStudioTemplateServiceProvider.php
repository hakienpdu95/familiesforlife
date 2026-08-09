<?php

namespace Modules\AIVideoStudioTemplate\Providers;

use Nwidart\Modules\Support\ModuleServiceProvider;

/**
 * spec/AIVideoStudioTemplate_Technical_Specification.md §5 — gate phẳng bằng permission string
 * 'ai_video_studio_template.use' đọc trực tiếp ở routes/web.php (middleware 'can:'), KHÔNG cần
 * Gate::define riêng ở đây — cùng nguyên tắc ContentOutlinesServiceProvider.
 */
class AIVideoStudioTemplateServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'AIVideoStudioTemplate';

    protected string $nameLower = 'aivideostudiotemplate';

    protected array $providers = [
        RouteServiceProvider::class,
    ];
}
