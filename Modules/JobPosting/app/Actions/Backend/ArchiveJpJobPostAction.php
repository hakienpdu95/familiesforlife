<?php

namespace Modules\JobPosting\Actions\Backend;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\JobPosting\Enums\HistoryChangeType;
use Modules\JobPosting\Enums\JobPostStatus;
use Modules\JobPosting\Events\JpJobPostStatusChanged;
use Modules\JobPosting\Models\JpJobPost;
use Modules\JobPosting\Models\JpJobPostHistory;

class ArchiveJpJobPostAction
{
    use AsAction;

    public function handle(JpJobPost $post): JpJobPost
    {
        $oldStatus = $post->status;

        $post->update([
            'status'     => JobPostStatus::Archived->value,
            'updated_by' => Auth::id(),
        ]);

        JpJobPostHistory::create([
            'uuid'        => Str::uuid()->toString(),
            'job_post_id' => $post->id,
            'change_type' => HistoryChangeType::Archived->value,
            'old_status'  => $oldStatus?->value,
            'new_status'  => JobPostStatus::Archived->value,
            'changed_by'  => Auth::id(),
        ]);

        event(new JpJobPostStatusChanged($post, $oldStatus, JobPostStatus::Archived));

        return $post->fresh();
    }
}
