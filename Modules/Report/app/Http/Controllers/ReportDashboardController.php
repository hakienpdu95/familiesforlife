<?php

namespace Modules\Report\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Shared\Tenancy\TenantContext;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReportDashboardController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        $canHr    = $user->hasAnyPermission(['reports.hr',   'reports.full']);
        $canSales = $user->hasAnyPermission(['reports.team', 'reports.personal', 'reports.full']);
        $canOps   = $user->hasAnyPermission(['reports.ops',  'reports.full']);

        if (!$canHr && !$canSales && !$canOps && !$user->hasPermissionTo('reports.shared')) {
            abort(403, 'Bạn không có quyền xem báo cáo.');
        }

        return view('report::index', compact('canHr', 'canSales', 'canOps'));
    }
}
