<?php

namespace Modules\Survey\Providers;

use Modules\Survey\Models\Survey;
use Modules\Survey\Models\SurveyResponse;
use Modules\Survey\Observers\SurveyObserver;
use Modules\Survey\WorkflowTriggers\SurveyResultBandTrigger;
use Modules\Survey\WorkflowTriggers\SurveySubmittedTrigger;
use Modules\WorkflowAutomation\Core\SubjectRegistry;
use Modules\WorkflowAutomation\Core\TriggerRegistry;
use Nwidart\Modules\Support\ModuleServiceProvider;

class SurveyServiceProvider extends ModuleServiceProvider
{
    protected string $name      = 'Survey';
    protected string $nameLower = 'survey';

    protected array $providers = [
        EventServiceProvider::class,
        RouteServiceProvider::class,
    ];

    public function boot(): void
    {
        parent::boot();
        Survey::observe(SurveyObserver::class);

        if (app()->bound(TriggerRegistry::class)) {
            $triggerRegistry = app(TriggerRegistry::class);
            $triggerRegistry->register(new SurveySubmittedTrigger());
            $triggerRegistry->register(new SurveyResultBandTrigger());
        }

        if (app()->bound(SubjectRegistry::class)) {
            app(SubjectRegistry::class)->register(
                type:            'SurveyResponse',
                fqcn:            SurveyResponse::class,
                label:           'Survey Response',
                updatableFields: [
                    ['field' => 'status', 'label' => 'Trạng thái', 'type' => 'integer'],
                ]
            );
        }
    }
}
