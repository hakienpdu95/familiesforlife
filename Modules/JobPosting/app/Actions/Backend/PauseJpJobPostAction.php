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

class PauseJpJobPostAction
{
    use AsAction;

    public function handle(JpJobPost $post, ?string $note = null): JpJobPost
    {
        $oldStatus = $post->status;

        $post->update([
            'status'     => JobPostStatus::Paused->value,
            'updated_by' => Auth::id(),
        ]);

        JpJobPostHistory::create([
            'uuid'        => Str::uuid()->toString(),
            'job_post_id' => $post->id,
            'change_type' => HistoryChangeType::StatusChanged->value,
            'old_status'  => $oldStatus?->value,
            'new_status'  => JobPostStatus::Paused->value,
            'note'        => $note,
            'changed_by'  => Auth::id(),
        ]);

        event(new JpJobPostStatusChanged($post, $oldStatus, JobPostStatus::Paused));

        return $post->fresh();
    }
}
