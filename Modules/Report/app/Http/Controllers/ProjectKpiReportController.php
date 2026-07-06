<?php

namespace Modules\Report\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class ProjectKpiReportController extends Controller
{
    public function projectIndex(): View { return view('report::project.index'); }
    public function tasks(): View        { return view('report::project.tasks'); }
    public function kpiIndex(): View     { return view('report::kpi.index'); }
    public function kpiCycle(): View     { return view('report::kpi.cycle'); }
    public function kpiSnapshot(): View  { return view('report::kpi.snapshot'); }
}
