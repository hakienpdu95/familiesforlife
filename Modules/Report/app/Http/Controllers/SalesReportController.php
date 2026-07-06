<?php

namespace Modules\Report\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class SalesReportController extends Controller
{
    public function index(): View      { return view('report::sales.index'); }
    public function pipeline(): View   { return view('report::sales.pipeline'); }
    public function conversion(): View { return view('report::sales.conversion'); }
    public function activity(): View   { return view('report::sales.activity'); }
}
