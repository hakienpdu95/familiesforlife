<?php

namespace Modules\Aicem\Providers;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Gate;
use Modules\Aicem\Models\AicemKnowledgeDocument;
use Modules\Aicem\Policies\AicemKnowledgeDocumentPolicy;
use Modules\Aicem\Policies\AicemWorkflowRunPolicy;
use Nwidart\Modules\Support\ModuleServiceProvider;

class AicemServiceProvider extends ModuleServiceProvider
{
    protected string $name      = 'Aicem';
    protected string $nameLower = 'aicem';

    protected array $providers = [
        EventServiceProvider::class,
        RouteServiceProvider::class,
    ];

    public function boot(): void
    {
        parent::boot();

        // config/aicem.php tự động merge vào key 'aicem' bởi ModuleServiceProvider::registerConfig()
        // (tên file == nameLower). aicem_subjects.php/aicem_content_verticals.php merge thủ công dưới
        // đây để giữ đúng top-level key phẳng mà spec/AICEM_Technical_Specification.md dùng xuyên suốt
        // (VD config("aicem_subjects.$subjectType.fields")), thay vì lồng dưới 'aicem.aicem_subjects.*'.
        $this->mergeConfigFrom(module_path($this->name, 'config/aicem_subjects.php'), 'aicem_subjects');
        $this->mergeConfigFrom(module_path($this->name, 'config/aicem_content_verticals.php'), 'aicem_content_verticals');

        Gate::policy(AicemKnowledgeDocument::class, AicemKnowledgeDocumentPolicy::class);

        // AicemWorkflowRunPolicy không gắn 1 Eloquent model cụ thể (chạy workflow/quyết định
        // suggestion không phải CRUD trên 1 resource) — đăng ký qua Gate::define thay vì Gate::policy.
        Gate::define('aicem.run_workflow', [AicemWorkflowRunPolicy::class, 'run']);
        Gate::define('aicem.decide_suggestion', [AicemWorkflowRunPolicy::class, 'decide']);

        // <x-aicem::panel> resolve qua namespace 'aicem' → class Modules\Aicem\View\Components\*
        // (component có class, khác pattern <x-mail::message> thuần view-only).
        Blade::componentNamespace('Modules\\Aicem\\View\\Components', 'aicem');
    }
}
