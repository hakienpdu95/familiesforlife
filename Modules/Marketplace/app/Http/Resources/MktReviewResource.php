<?php

namespace Modules\Marketplace\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MktReviewResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                    => $this->uuid,
            'reviewer_type'         => $this->reviewer_type?->value,
            'reviewer_type_label'   => $this->reviewer_type?->label(),
            'relation_type'         => $this->relation_type?->value,
            'relation_type_label'   => $this->relation_type?->label(),
            'overall_rating'        => $this->overall_rating,
            'title'                 => $this->title,
            'content'               => $this->content,
            'rating_quality'        => $this->rating_quality,
            'rating_communication'  => $this->rating_communication,
            'rating_punctuality'    => $this->rating_punctuality,
            'is_public'             => $this->is_public,
            'created_at'            => $this->created_at?->toISOString(),
            'reviewer_name'         => $this->when(
                $this->is_public,
                fn() => match ($this->reviewer_type?->value) {
                    'applicant' => $this->application?->applicant?->display_name,
                    'org'       => $this->application?->listing?->organization?->name,
                    default     => null,
                }
            ),
        ];
    }
}
