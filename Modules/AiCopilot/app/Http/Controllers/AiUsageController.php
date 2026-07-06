<?php

namespace Modules\AiCopilot\Http\Controllers;

use App\Enums\PermissionEnum as P;
use App\Http\Controllers\Controller;
use Modules\AiCopilot\Queries\GetUsageSummaryQuery;

class AiUsageController extends Controller
{
    public function index()
    {
        $this->authorize(P::AI_COPILOT_VIEW_USAGE->value);

        $summary = (new GetUsageSummaryQuery())->run();

        return view('ai_copilot::usage.index', $summary);
    }
}
