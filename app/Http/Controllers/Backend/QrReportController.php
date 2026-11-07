<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Shared\Traceability\Models\QrReport;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class QrReportController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('manageQrReports');

        $status = $request->query('status') === 'resolved' ? 'resolved' : 'new';

        $reports = QrReport::query()
            ->with(['resolvedBy:id,name', 'identity.target' => fn (MorphTo $morph) => $morph->withTrashed()])
            ->where('status', $status)
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        return view('backend.qr-reports.index', [
            'reports' => $reports,
            'status' => $status,
            'counts' => QrReport::query()->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status'),
        ]);
    }

    public function resolve(QrReport $report): RedirectResponse
    {
        $this->authorize('manageQrReports');

        $report->update(['status' => 'resolved', 'resolved_by' => auth()->id(), 'resolved_at' => now()]);

        return back()->with('success', 'Đã đánh dấu phản ánh #'.$report->id.' là đã xử lý.');
    }
}
