<?php

namespace Modules\Recruitment\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Recruitment\Actions\Backend\CreateOfferAction;
use Modules\Recruitment\Actions\Backend\HandoffAction;
use Modules\Recruitment\Models\RcApplication;
use Modules\Recruitment\Models\RcOffer;

class OfferController extends Controller
{
    public function create(Request $request): View
    {
        $this->authorize('create', RcApplication::class);

        $application = RcApplication::with(['candidate', 'currentStage'])->findOrFail($request->query('application_id'));

        return view('recruitment::offers.create', compact('application'));
    }

    public function store(Request $request, RcApplication $application, CreateOfferAction $action): RedirectResponse
    {
        $this->authorize('update', $application);

        $validated = $request->validate([
            'salary_offered' => ['required', 'numeric', 'min:0'],
            'currency'       => ['nullable', 'string', 'size:3'],
            'start_date'     => ['required', 'date', 'after_or_equal:today'],
            'probation_days' => ['required', 'integer', 'min:0', 'max:365'],
            'benefits_note'  => ['nullable', 'string'],
            'expire_at'      => ['nullable', 'date', 'after_or_equal:today'],
        ]);

        $offer = $action->handle($application, $validated);

        return redirect()
            ->route('backend.recruitment.offers.show', $offer)
            ->with('success', 'Đã tạo offer');
    }

    public function show(RcOffer $offer): View
    {
        $this->authorize('view', $offer->application);

        $offer->load([
            'application.candidate',
            'application.currentStage',
            'approvedBy',
            'createdBy',
        ]);

        return view('recruitment::offers.show', compact('offer'));
    }

    public function approve(RcOffer $offer): JsonResponse
    {
        $this->authorize('update', $offer->application);

        abort_if($offer->status !== \Modules\Recruitment\Enums\OfferStatus::PendingApproval, 422, 'Offer không ở trạng thái chờ duyệt');

        $offer->update([
            'status'      => 'approved',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);

        return response()->json(['message' => 'Đã duyệt offer', 'status' => 'approved']);
    }

    public function submitForApproval(RcOffer $offer): JsonResponse
    {
        $this->authorize('update', $offer->application);

        abort_if($offer->status !== \Modules\Recruitment\Enums\OfferStatus::Draft, 422, 'Chỉ bản nháp mới có thể gửi duyệt');

        $offer->update(['status' => 'pending_approval']);

        return response()->json(['message' => 'Đã gửi yêu cầu duyệt', 'status' => 'pending_approval']);
    }

    public function send(RcOffer $offer): JsonResponse
    {
        $this->authorize('update', $offer->application);

        abort_if(
            !in_array($offer->status?->value, ['approved', 'draft']),
            422,
            'Offer phải được duyệt trước khi gửi'
        );

        $offer->update([
            'status'  => 'sent',
            'sent_at' => now(),
        ]);

        return response()->json(['message' => 'Đã gửi offer đến ứng viên', 'status' => 'sent']);
    }

    public function accept(RcOffer $offer, HandoffAction $handoff): JsonResponse
    {
        $this->authorize('update', $offer->application);

        abort_if($offer->status !== \Modules\Recruitment\Enums\OfferStatus::Sent, 422, 'Offer chưa được gửi đến ứng viên');

        // BR-RC-006: cần jp_job_post_id trước handoff
        if (!$offer->application->jp_job_post_id) {
            return response()->json([
                'message' => 'Cần liên kết tin tuyển dụng (jp_job_post_id) trước khi thực hiện handoff',
                'warning' => true,
            ], 422);
        }

        $offer->update([
            'status'       => 'accepted',
            'responded_at' => now(),
        ]);

        $employee = $handoff->handle($offer);

        return response()->json([
            'message'     => 'Offer được chấp nhận — ứng viên đã được tạo employee',
            'employee_id' => $employee->id,
            'status'      => 'accepted',
        ]);
    }

    public function reject(Request $request, RcOffer $offer): JsonResponse
    {
        $this->authorize('update', $offer->application);

        abort_if($offer->status?->isTerminal(), 422, 'Offer đã kết thúc');

        $offer->update([
            'status'           => 'rejected',
            'responded_at'     => now(),
            'rejection_reason' => $request->input('reason'),
        ]);

        return response()->json(['message' => 'Đã từ chối offer', 'status' => 'rejected']);
    }

    public function revoke(RcOffer $offer): JsonResponse
    {
        $this->authorize('update', $offer->application);

        abort_if($offer->status?->isTerminal(), 422, 'Offer đã kết thúc');

        $offer->update(['status' => 'revoked']);

        return response()->json(['message' => 'Đã thu hồi offer', 'status' => 'revoked']);
    }
}
