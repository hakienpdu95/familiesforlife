<?php

namespace Modules\Recruitment\Actions\Backend;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Recruitment\Models\RcCandidate;
use Modules\Recruitment\Models\RcCandidateNote;

class StoreCandidateNoteAction
{
    use AsAction;

    public function handle(RcCandidate $candidate, array $data): RcCandidateNote
    {
        return RcCandidateNote::create([
            'candidate_id'   => $candidate->id,
            'application_id' => $data['application_id'] ?? null,
            'content'        => $data['content'],
            'note_type'      => $data['note_type'] ?? 'general',
            'is_private'     => (bool) ($data['is_private'] ?? false),
            'created_by'     => auth()->id(),
        ]);
    }
}
