<?php

namespace Modules\Marketplace\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use Modules\Marketplace\Enums\ReviewerType;
use Modules\Marketplace\Enums\ReviewRelationType;

class MktReview extends Model
{
    protected $table = 'mkt_reviews';
    public $timestamps = false;

    protected $fillable = [
        'uuid', 'listing_id', 'application_id',
        'reviewer_type', 'reviewer_id', 'relation_type',
        'overall_rating', 'title', 'content',
        'rating_quality', 'rating_communication', 'rating_punctuality',
        'is_public',
    ];

    protected $casts = [
        'reviewer_type'  => ReviewerType::class,
        'relation_type'  => ReviewRelationType::class,
        'is_public'      => 'boolean',
        'created_at'     => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model): void {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
            if (empty($model->created_at)) {
                $model->created_at = now();
            }
        });

        // After creating an org review, update applicant avg_rating
        static::created(function (self $review): void {
            if ($review->reviewer_type === ReviewerType::Org) {
                $application = MktApplication::find($review->application_id);
                if ($application) {
                    static::recalcApplicantRating($application->applicant_id);
                }
            }
        });
    }

    public static function recalcApplicantRating(int $applicantId): void
    {
        $avg = static::where('reviewer_type', ReviewerType::Org->value)
            ->whereHas('application', fn($q) => $q->where('applicant_id', $applicantId))
            ->where('is_public', true)
            ->avg('overall_rating');

        MktApplicant::where('id', $applicantId)->update([
            'avg_rating' => $avg ? round($avg, 2) : null,
        ]);
    }

    public function listing(): BelongsTo
    {
        return $this->belongsTo(MktListing::class, 'listing_id');
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(MktApplication::class, 'application_id');
    }

    // reviewer() is not a standard BelongsTo because it's polymorphic by type
    public function getReviewerAttribute(): User|MktApplicant|null
    {
        return match ($this->reviewer_type) {
            ReviewerType::Org       => User::find($this->reviewer_id),
            ReviewerType::Applicant => MktApplicant::find($this->reviewer_id),
            default                 => null,
        };
    }
}
