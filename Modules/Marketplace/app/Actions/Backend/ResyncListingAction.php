<?php
namespace Modules\Marketplace\Actions\Backend;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Marketplace\Enums\JpSyncStatus;
use Modules\Marketplace\Models\MktListing;

class ResyncListingAction
{
    use AsAction;

    public function handle(MktListing $listing): bool
    {
        if (! $listing->jp_job_post_id) {
            return false;
        }

        if (! class_exists(\Modules\JobPosting\Models\JpJobPost::class)) {
            return false;
        }

        $jpPost = \Modules\JobPosting\Models\JpJobPost::withoutGlobalScope('tenant')
            ->where('uuid', $listing->jp_job_post_id)
            ->first();

        if (! $jpPost) {
            return false;
        }

        $listing->update([
            'title'            => $jpPost->title,
            'description'      => $jpPost->description,
            'requirements'     => $jpPost->requirements ?? $listing->requirements,
            'employment_type'  => $jpPost->employment_type ?? $listing->employment_type,
            'experience_level' => $jpPost->experience_level ?? $listing->experience_level,
            'work_type'        => $jpPost->work_arrangement ?? $listing->work_type,
            'salary_min'       => $jpPost->salary_min ?? $listing->salary_min,
            'salary_max'       => $jpPost->salary_max ?? $listing->salary_max,
            'headcount'        => $jpPost->headcount ?? $listing->headcount,
            'expire_at'        => $jpPost->expire_at ?? $listing->expire_at,
            'jp_sync_status'   => JpSyncStatus::SYNCED->value,
        ]);

        return true;
    }
}
