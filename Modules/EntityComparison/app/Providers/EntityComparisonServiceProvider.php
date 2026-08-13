<?php

namespace Modules\EntityComparison\Providers;

use Illuminate\Support\Facades\Gate;
use Modules\EntityComparison\Models\Criterion;
use Modules\EntityComparison\Models\Entity;
use Modules\EntityComparison\Models\EntityType;
use Modules\EntityComparison\Policies\CriterionPolicy;
use Modules\EntityComparison\Policies\EntityPolicy;
use Modules\EntityComparison\Policies\EntityTypePolicy;
use Nwidart\Modules\Support\ModuleServiceProvider;

/** spec/Entity_Comparison_Module_Technical_Spec.md §8 — đúng mẫu OcopServiceProvider. */
class EntityComparisonServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'EntityComparison';

    protected string $nameLower = 'entitycomparison';

    protected array $providers = [
        RouteServiceProvider::class,
    ];

    public function register(): void
    {
        parent::register();

        // nwidart đăng ký config module dưới key lồng `entitycomparison.entity_comparison` —
        // expose lại ở key top-level `entity_comparison` để code đọc gọn
        // (config('entity_comparison.max_compare_entities')), đúng pattern
        // ContentCalendarServiceProvider/CoreIdeaExtractorServiceProvider.
        $this->mergeConfigFrom(
            __DIR__.'/../../config/entity_comparison.php',
            'entity_comparison'
        );
    }

    public function boot(): void
    {
        parent::boot();

        Gate::policy(EntityType::class, EntityTypePolicy::class);
        Gate::policy(Criterion::class, CriterionPolicy::class);
        Gate::policy(Entity::class, EntityPolicy::class);
    }
}
