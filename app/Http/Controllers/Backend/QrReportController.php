<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Shared\Traceability\Models\QrIdentity;
use App\Shared\Traceability\Models\QrReport;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class QrReportController extends Controller
{
    private const SORTABLE = ['created_at', 'reason', 'resolved_at'];

    public function index(): View
    {
        $this->authorize('manageQrReports');

        return view('backend.qr-reports.index', [
            'reasons' => array_map('__', QrReport::REASONS),
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        $this->authorize('manageQrReports');

        $validated = $request->validate([
            'page' => ['nullable', 'integer', 'min:1'],
            'size' => ['nullable', 'integer', 'min:5', 'max:100'],
            'status' => ['nullable', Rule::in(['new', 'resolved'])],
            'reason' => ['nullable', Rule::in(array_keys(QrReport::REASONS))],
            'search' => ['nullable', 'string', 'max:200'],
        ]);

        $sortRaw = $request->input('sort.0');
        $sortField = is_array($sortRaw) && in_array($sortRaw['field'] ?? null, self::SORTABLE, true) ? $sortRaw['field'] : 'created_at';
        $sortDir = is_array($sortRaw) && ($sortRaw['dir'] ?? '') === 'asc' ? 'asc' : 'desc';
        $search = trim((string) ($validated['search'] ?? ''));

        $paginator = QrReport::query()
            ->with([
                'resolvedBy:id,name',
                'identity' => fn ($q) => $q->with(QrIdentity::targetEagerLoad()),
            ])
            ->when($validated['status'] ?? null, fn ($q, $status) => $q->where('status', $status))
            ->when($validated['reason'] ?? null, fn ($q, $reason) => $q->where('reason', $reason))
            ->when($search !== '', fn ($q) => $q->where(fn ($w) => $w
                ->where('content', 'like', "%{$search}%")
                ->orWhere('contact', 'like', "%{$search}%")
                ->orWhereHas('identity', fn ($i) => $i->where('qr_code', $search))
                ->when(QrReport::idFromRef($search), fn ($r, $id) => $r->orWhere('id', $id))))
            ->orderBy($sortField, $sortDir)
            ->orderBy('id', $sortDir)
            ->paginate(min(100, max(5, (int) ($validated['size'] ?? 25))), ['*'], 'page', max(1, (int) ($validated['page'] ?? 1)));

        return response()->json([
            'data' => collect($paginator->items())->map(fn (QrReport $r) => $this->row($r))->values(),
            'last_page' => $paginator->lastPage(),
            'total' => $paginator->total(),
            'counts' => QrReport::query()->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status'),
        ]);
    }

    public function resolve(Request $request, QrReport $report): RedirectResponse|JsonResponse
    {
        $this->authorize('manageQrReports');

        $report->update(['status' => 'resolved', 'resolved_by' => auth()->id(), 'resolved_at' => now()]);

        $message = __('Đã đánh dấu phản ánh #:id là đã xử lý.', ['id' => $report->id]);

        if ($request->expectsJson()) {
            return response()->json(['message' => $message]);
        }

        return back()->with('success', $message);
    }

    private function row(QrReport $report): array
    {
        $identity = $report->identity;

        return [
            'id' => $report->id,
            'ref' => $report->ref(),
            'created_at' => format_datetime($report->created_at),
            'qr_code' => $identity?->qr_code,
            'qr_url' => $identity?->publicUrl(),
            'target_kind' => $identity?->targetKindLabel(),
            'target_title' => $identity?->targetTitle() ?: __('(đã xoá)'),
            'target_trashed' => (bool) $identity?->targetTrashed(),
            'reason' => $report->reason,
            'reason_label' => $report->reasonLabel(),
            'content' => $report->content,
            'contact' => $report->contact,
            'status' => $report->status,
            'resolved_by' => $report->resolvedBy?->name,
            'resolved_at' => format_datetime($report->resolved_at),
            'resolve_url' => $report->status === 'new' ? route('backend.qr-reports.resolve', $report) : null,
        ];
    }
}
