<?php

namespace Modules\Report\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class HrReportController extends Controller
{
    public function index(): View   { return view('report::hr.index'); }
    public function headcount(): View { return view('report::hr.headcount'); }
}
