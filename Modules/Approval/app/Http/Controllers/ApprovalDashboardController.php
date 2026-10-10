<?php

namespace Modules\Approval\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

/**
 * spec/Workflow_Approval_Technical_Specification.md §12.
 */
class ApprovalDashboardController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewDashboard'); // Gate::define riêng — permission approval.view_dashboard HOẶC content_moderator

        $subjectTypes = collect(config('approval.subjects'))->map(fn (array $config) => $config['label']);

        return view('approval::dashboard.index', compact('subjectTypes'));
    }
}
