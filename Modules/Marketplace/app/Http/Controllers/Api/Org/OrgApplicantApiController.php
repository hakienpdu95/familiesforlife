<?php
namespace Modules\Marketplace\Http\Controllers\Api\Org;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Marketplace\Actions\Backend\ImportApplicationToRcAction;
use Modules\Marketplace\Actions\Backend\RejectApplicationAction;
use Modules\Marketplace\Actions\Backend\ShortlistApplicationAction;
use Modules\Marketplace\Http\Resources\MktApplicationResource;
use Modules\Marketplace\Models\MktApplication;
use Modules\Marketplace\Models\MktListing;

class OrgApplicantApiController extends Controller
{
    public function listApplicants(Request $request, MktListing $listing): JsonResponse
    {
        $this->authorize('view', $listing);

        $applications = MktApplication::with(['applicant'])
            ->where('listing_id', $listing->id)
            ->orderByDesc('applied_at')
            ->paginate(25);

        // Mark viewed_at for submitted applications
        MktApplication::where('listing_id', $listing->id)
            ->where('status', 'submitted')
            ->whereNull('viewed_at')
            ->update(['viewed_at' => now(), 'status' => 'viewed']);

        return response()->json([
            'data'      => MktApplicationResource::collection($applications->items()),
            'last_page' => $applications->lastPage(),
            'total'     => $applications->total(),
        ]);
    }

    public function shortlist(MktApplication $application, ShortlistApplicationAction $action): JsonResponse
    {
        $this->authorize('update', $application->listing()->withoutGlobalScope('tenant')->first());

        $action->handle($application);

        return response()->json(['message' => 'Đã shortlist ứng viên.']);
    }

    public function reject(MktApplication $application, RejectApplicationAction $action): JsonResponse
    {
        $this->authorize('update', $application->listing()->withoutGlobalScope('tenant')->first());

        $action->handle($application);

        return response()->json(['message' => 'Đã từ chối ứng viên.']);
    }

    public function import(MktApplication $application, ImportApplicationToRcAction $action): JsonResponse
    {
        $listing = $application->listing()->withoutGlobalScope('tenant')->first();
        $this->authorize('update', $listing);

        $result = $action->handle($application, auth()->user());

        return match ($result['status']) {
            'already_imported' => response()->json(['message' => 'Ứng viên đã được import trước đó.'], 422),
            'no_jp_post'       => response()->json(['message' => 'Tin đăng không liên kết với Job Posting.'], 422),
            'rc_unavailable'   => response()->json(['message' => 'Module Recruitment chưa khả dụng.'], 503),
            default            => response()->json(['message' => 'Import thành công.', 'result' => $result]),
        };
    }
}
