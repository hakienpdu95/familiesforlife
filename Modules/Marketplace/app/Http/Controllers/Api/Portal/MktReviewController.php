<?php

namespace Modules\Marketplace\Http\Controllers\Api\Portal;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Marketplace\Actions\Portal\StoreReviewAction;
use Modules\Marketplace\Data\Requests\StoreReviewData;
use Modules\Marketplace\Enums\ReviewerType;
use Modules\Marketplace\Http\Resources\MktReviewResource;
use Modules\Marketplace\Models\MktApplicant;
use Modules\Marketplace\Models\MktApplication;
use Modules\Marketplace\Models\MktReview;

class MktReviewController extends Controller
{
    public function store(Request $request, string $uuid, StoreReviewAction $action): JsonResponse
    {
        $applicant   = $request->user('marketplace');
        $application = MktApplication::where('uuid', $uuid)
            ->where('applicant_id', $applicant->id)
            ->firstOrFail();

        if (! $application->canReview()) {
            return response()->json(['message' => 'Chỉ có thể đánh giá sau khi được tuyển dụng.'], 422);
        }

        $alreadyReviewed = MktReview::where('application_id', $application->id)
            ->where('reviewer_type', ReviewerType::Applicant->value)
            ->where('reviewer_id', $applicant->id)
            ->exists();

        if ($alreadyReviewed) {
            return response()->json(['message' => 'Bạn đã đánh giá đơn ứng tuyển này rồi.'], 422);
        }

        $data   = StoreReviewData::validateAndCreate($request->all());
        $review = $action->handle($application, $data, ReviewerType::Applicant, $applicant->id);

        return response()->json(new MktReviewResource($review), 201);
    }

    public function profileReviews(string $slug): JsonResponse
    {
        $applicant = MktApplicant::where('slug', $slug)->firstOrFail();

        $reviews = MktReview::with(['application.listing.organization'])
            ->whereHas('application', fn($q) => $q->where('applicant_id', $applicant->id))
            ->where('is_public', true)
            ->orderByDesc('created_at')
            ->paginate(10);

        return response()->json([
            'data'      => MktReviewResource::collection($reviews->items()),
            'last_page' => $reviews->lastPage(),
            'total'     => $reviews->total(),
            'avg_rating' => $applicant->avg_rating,
        ]);
    }
}
