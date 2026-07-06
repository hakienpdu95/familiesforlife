<?php

namespace Modules\JobPosting\Observers;

use Illuminate\Support\Str;
use Modules\JobPosting\Enums\JobPostStatus;
use Modules\JobPosting\Enums\MktSyncStatus;
use Modules\JobPosting\Models\JpJobPost;
use Modules\JobPosting\Models\MktListing;

class JpJobPostObserver
{
    public function updated(JpJobPost $post): void
    {
        $statusChanged = $post->wasChanged('status');
        $newStatus     = $post->status;

        // Publish → sync ra Marketplace
        if ($statusChanged && $newStatus === JobPostStatus::Published && $post->publish_to_marketplace) {
            $this->syncToMarketplace($post);
            return;
        }

        // Close / Archive → đóng listing trên Marketplace
        if ($statusChanged && in_array($newStatus, [JobPostStatus::Closed, JobPostStatus::Archived, JobPostStatus::Cancelled])) {
            $this->closeMarketplaceListing($post);
            return;
        }

        // Nội dung thay đổi khi đã publish → đánh dấu out_of_sync (không dùng save() để tránh loop)
        if (! $statusChanged && $post->mkt_listing_id !== null && $post->publish_to_marketplace) {
            $contentFields = [
                'title', 'description', 'requirements', 'responsibilities',
                'salary_min', 'salary_max', 'employment_type', 'work_arrangement',
                'experience_level', 'city', 'province', 'headcount', 'expire_at',
            ];
            if ($post->wasChanged($contentFields)) {
                JpJobPost::withoutEvents(function () use ($post) {
                    JpJobPost::where('id', $post->id)->update(['mkt_sync_status' => MktSyncStatus::OutOfSync->value]);
                });
            }
        }
    }

    public function syncToMarketplace(JpJobPost $post): void
    {
        $location = collect([$post->city, $post->province])->filter()->implode(', ');

        if ($post->mkt_listing_id) {
            // Update existing listing
            MktListing::where('uuid', $post->mkt_listing_id)->update([
                'title'            => $post->title,
                'description'      => $post->description,
                'requirements'     => $post->requirements,
                'salary_min'       => $post->salary_min,
                'salary_max'       => $post->salary_max,
                'salary_currency'  => $post->salary_currency,
                'employment_type'  => $post->employment_type?->value,
                'work_type'        => $post->work_arrangement?->value,
                'experience_level' => $post->experience_level?->value,
                'location'         => $location ?: null,
                'headcount'        => $post->headcount,
                'expire_at'        => $post->expire_at,
                'status'           => 'active',
                'closed_at'        => null,
            ]);

            JpJobPost::where('id', $post->id)->update(['mkt_sync_status' => MktSyncStatus::Synced->value]);
        } else {
            // Insert new listing
            $uuid = Str::uuid()->toString();

            MktListing::create([
                'uuid'             => $uuid,
                'jp_job_post_id'   => $post->uuid,
                'org_id'           => $post->organization_id,
                'poster_type'      => 'org',
                'listing_type'     => 'job',
                'title'            => $post->title,
                'description'      => $post->description,
                'requirements'     => $post->requirements,
                'salary_min'       => $post->salary_min,
                'salary_max'       => $post->salary_max,
                'salary_currency'  => $post->salary_currency,
                'employment_type'  => $post->employment_type?->value,
                'work_type'        => $post->work_arrangement?->value,
                'experience_level' => $post->experience_level?->value,
                'location'         => $location ?: null,
                'headcount'        => $post->headcount,
                'expire_at'        => $post->expire_at,
                'status'           => 'active',
            ]);

            JpJobPost::where('id', $post->id)->update([
                'mkt_listing_id'  => $uuid,
                'mkt_sync_status' => MktSyncStatus::Synced->value,
            ]);
        }
    }

    private function closeMarketplaceListing(JpJobPost $post): void
    {
        if (! $post->mkt_listing_id) {
            return;
        }

        MktListing::where('uuid', $post->mkt_listing_id)->update([
            'status'    => 'closed',
            'closed_at' => now(),
        ]);
    }
}
