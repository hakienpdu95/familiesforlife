<?php

namespace Modules\Aicem\Features\ExampleLearning\Actions;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Aicem\Enums\ExampleCandidateStatus;
use Modules\Aicem\Models\AicemExampleCandidate;

class RejectExampleCandidateAction
{
    use AsAction;

    public function handle(AicemExampleCandidate $candidate, int $userId): AicemExampleCandidate
    {
        if ($candidate->status !== ExampleCandidateStatus::Pending) {
            throw new \RuntimeException("Candidate đã được xử lý trước đó ({$candidate->status->value}).");
        }

        $candidate->update([
            'status'      => ExampleCandidateStatus::Rejected,
            'reviewed_by' => $userId,
            'reviewed_at' => now(),
        ]);

        return $candidate;
    }
}
