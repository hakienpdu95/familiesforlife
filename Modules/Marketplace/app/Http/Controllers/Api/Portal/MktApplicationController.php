<?php
namespace Modules\Marketplace\Http\Controllers\Api\Portal;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Marketplace\Actions\Portal\ApplyListingAction;
use Modules\Marketplace\Actions\Portal\WithdrawApplicationAction;
use Modules\Marketplace\Data\Requests\StoreApplicationData;
use Modules\Marketplace\Enums\ApplicationStatus;
use Modules\Marketplace\Enums\ListingStatus;
use Modules\Marketplace\Http\Resources\MktApplicationResource;
use Modules\Marketplace\Models\MktApplication;
use Modules\Marketplace\Models\MktListing;

class MktApplicationController extends Controller
{
    public function myApplications(Request $request): JsonResponse
    {
        $applicant    = $request->user('marketplace');
        $applications = MktApplication::with(['listing.organization'])
            ->where('applicant_id', $applicant->id)
            ->orderByDesc('applied_at')
            ->paginate(20);

        return response()->json([
            'data'      => MktApplicationResource::collection($applications->items()),
            'last_page' => $applications->lastPage(),
            'total'     => $applications->total(),
        ]);
    }

    public function apply(Request $request, string $slug, ApplyListingAction $action): JsonResponse
    {
        $applicant = $request->user('marketplace');

        $listing = MktListing::withoutGlobalScope('tenant')
            ->where('slug', $slug)
            ->firstOrFail();

        if ($listing->status !== ListingStatus::ACTIVE) {
            return response()->json(['message' => 'Tin đăng không còn hoạt động.'], 422);
        }

        if ($applicant->hasApplied($listing->id)) {
            return response()->json(['message' => 'Bạn đã nộp đơn cho tin này rồi.'], 422);
        }

        $data        = StoreApplicationData::validateAndCreate($request->all());
        $application = $action->handle($listing, $applicant, $data);
        $applicant->increment('total_applications');

        return response()->json(new MktApplicationResource($application->load('listing.organization')), 201);
    }

    public function withdraw(Request $request, string $uuid, WithdrawApplicationAction $action): JsonResponse
    {
        $applicant   = $request->user('marketplace');
        $application = MktApplication::where('uuid', $uuid)->firstOrFail();

        if ($application->applicant_id !== $applicant->id) {
            return response()->json(['message' => 'Không có quyền thực hiện hành động này.'], 403);
        }

        if (! $application->canWithdraw()) {
            return response()->json(['message' => 'Không thể rút đơn ở trạng thái này.'], 422);
        }

        $action->handle($application);

        return response()->json(['message' => 'Đã rút đơn ứng tuyển.']);
    }
}
