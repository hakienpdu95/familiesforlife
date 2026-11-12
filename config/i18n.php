<?php

use App\Http\Controllers\Backend\QrReportController;
use App\Shared\Traceability\Models\QrReport;
use Modules\ActivityLog\Http\Controllers\ActivityLogApiController;
use Modules\AIVideoStudioTemplate\Features\FormulaAdvisor\Support\PersonaCatalog;
use Modules\AIVideoStudioTemplate\Features\FormulaAdvisor\Support\ProductFormulaCatalog;
use Modules\AIVideoStudioTemplate\Models\AiVideoStudioProject;
use Modules\Approval\Models\ApprovalLog;
use Modules\Ocop\Features\Traceability\OcopProductTracePresenter;
use Modules\Ocop\Models\OcopProduct;
use Modules\OcopSubject\Features\PublicReading\Queries\ListBrandProductsQuery;
use Modules\OcopSubject\Models\OcopSubject;
use Modules\Page\Features\PageManagement\PageTemplate;

return [

    'source_locale' => 'vi',

    'target_locales' => ['en'],

    'portal_locales' => ['vi', 'en'],

    'portal_default_locale' => 'vi',

    'portal_locale_labels' => [
        'vi' => 'Tiếng Việt',
        'en' => 'English',
    ],

    'portal_routes' => [
        'post.public.*',
        'event.public.*',
        'heritage.public.*',
        'ocop.public.*',
        'ocop-subject.public.*',
        'province.public.*',
        'playlist.public.*',
        'real-estate.public.*',
        'page.public.*',
        'newsletter.public.subscribe',
        'video.index',
    ],

    'portal_routes_except' => [
        'post.public.sitemap',
        'post.public.robots',
        'post.public.llms',
        'event.public.sitemap',
    ],

    'scan_paths' => [
        'app',
        'Modules',
        'resources/views',
        'routes',
    ],

    'js_paths' => [
        'resources/js',
        'Modules',
    ],

    'exclude' => [
        'vendor',
        'node_modules',
        'tests',
        'database',
        'resources/views/vendor',
    ],

    'translatable_constants' => [
        QrReport::class.'::REASONS',
        QrReport::class.'::REASON_HINTS',
        QrReportController::class.'::KIND_LABELS',
        ApprovalLog::class.'::ACTION_LABELS',
        OcopProduct::class.'::SHELF_LIFE_UNITS',
        OcopSubject::class.'::DOCUMENT_LABELS',
        ListBrandProductsQuery::class.'::SORTS',
        PageTemplate::class.'::MAP',
        OcopProductTracePresenter::class.'::QUALITY_DOCS',
        OcopProductTracePresenter::class.'::SECTIONS',
        ActivityLogApiController::class.'::FIELD_LABELS',
        AiVideoStudioProject::class.'::VIDEO_TYPES',
        AiVideoStudioProject::class.'::VIDEO_FORMULAS',
        AiVideoStudioProject::class.'::ASPECT_RATIOS',
        AiVideoStudioProject::class.'::RESOLUTIONS',
        AiVideoStudioProject::class.'::STATUSES',
        PersonaCatalog::class.'::PERSONAS|*.name,*.trust_driver,*.warning',
        ProductFormulaCatalog::class.'::PRODUCTS|*.name,*.note,*.warning',
        ProductFormulaCatalog::class.'::BANNED_CATEGORIES|*.name,*.note',
        ProductFormulaCatalog::class.'::FORBIDDEN_COMBOS|*.pair,*.consequence',
        ProductFormulaCatalog::class.'::PRODUCTION_BUDGET|*.time,*.role',
    ],

    'translatable_config' => [
        'breadcrumbs.segments',
        'approval.subjects.*.label',
        'prompt_framework_studio.frameworks.*.name',
        'prompt_framework_studio.frameworks.*.description',
        'prompt_framework_studio.frameworks.*.best_for',
        'prompt_framework_studio.frameworks.*.group',
        'prompt_framework_studio.frameworks.*.fields.*.label',
        'prompt_framework_studio.frameworks.*.fields.*.hint',
        'prompt_framework_studio.frameworks.*.fields.*.tip',
        'prompt_framework_studio.frameworks.*.fields.*.options',
        'prompt_framework_studio.frameworks.*.example',
    ],

];
