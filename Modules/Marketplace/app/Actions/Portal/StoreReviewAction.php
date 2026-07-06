<?php

namespace Modules\Marketplace\Actions\Portal;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Marketplace\Data\Requests\StoreReviewData;
use Modules\Marketplace\Enums\ApplicationStatus;
use Modules\Marketplace\Enums\ReviewerType;
use Modules\Marketplace\Models\MktApplication;
use Modules\Marketplace\Models\MktReview;

class StoreReviewAction
{
    use AsAction;

    public function handle(
        MktApplication $application,
        StoreReviewData $data,
        ReviewerType $reviewerType,
        int $reviewerId
    ): MktReview {
        return MktReview::create([
            'listing_id'           => $application->listing_id,
            'application_id'       => $application->id,
            'reviewer_type'        => $reviewerType->value,
            'reviewer_id'          => $reviewerId,
            'relation_type'        => $data->relation_type->value,
            'overall_rating'       => $data->overall_rating,
            'title'                => $data->title,
            'content'              => $data->content,
            'rating_quality'       => $data->rating_quality,
            'rating_communication' => $data->rating_communication,
            'rating_punctuality'   => $data->rating_punctuality,
        ]);
    }
}
