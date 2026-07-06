<?php

namespace Modules\Marketplace\Http\Controllers\Api\Org;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Marketplace\Actions\Portal\StoreReviewAction;
use Modules\Marketplace\Data\Requests\StoreReviewData;
use Modules\Marketplace\Enums\ReviewerType;
use Modules\Marketplace\Http\Resources\MktReviewResource;
use Modules\Marketplace\Models\MktApplication;
use Modules\Marketplace\Models\MktReview;

class OrgReviewController extends Controller
{
    public function store(Request $request, MktApplication $application, StoreReviewAction $action): JsonResponse
    {
        $listing = $application->listing()->withoutGlobalScope('tenant')->first();
        $this->authorize('update', $listing);

        if (! $application->canReview()) {
            return response()->json(['message' => 'Chỉ có thể đánh giá sau khi ứng viên đã được tuyển dụng.'], 422);
        }

        $alreadyReviewed = MktReview::where('application_id', $application->id)
            ->where('reviewer_type', ReviewerType::Org->value)
            ->where('reviewer_id', auth()->id())
            ->exists();

        if ($alreadyReviewed) {
            return response()->json(['message' => 'Đã đánh giá ứng viên này rồi.'], 422);
        }

        $data   = StoreReviewData::validateAndCreate($request->all());
        $review = $action->handle($application, $data, ReviewerType::Org, auth()->id());

        return response()->json(new MktReviewResource($review), 201);
    }
}
