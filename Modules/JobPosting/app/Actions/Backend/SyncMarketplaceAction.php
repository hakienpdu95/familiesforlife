<?php

namespace Modules\JobPosting\Actions\Backend;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\JobPosting\Enums\JobPostStatus;
use Modules\JobPosting\Models\JpJobPost;
use Modules\JobPosting\Observers\JpJobPostObserver;

class SyncMarketplaceAction
{
    use AsAction;

    public function handle(JpJobPost $post): void
    {
        if (! $post->publish_to_marketplace || $post->status !== JobPostStatus::Published) {
            return;
        }

        (new JpJobPostObserver())->syncToMarketplace($post);

        // Reload so the controller sees updated mkt_sync_status
        $post->refresh();
    }
}
