<?php

namespace Modules\Lead\Actions\Notes;

use Illuminate\Support\Facades\Auth;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Lead\Data\Requests\StoreNoteData;
use Modules\Lead\Models\Lead;
use Modules\Lead\Models\LeadNote;

class StoreNoteAction
{
    use AsAction;

    public function handle(Lead $lead, StoreNoteData $data): LeadNote
    {
        $user = Auth::user();

        return LeadNote::create([
            'lead_id'         => $lead->id,
            'organization_id' => $lead->organization_id,
            'content'         => $data->content,
            'is_pinned'       => false,
            'author_id'       => $user?->id,
            'author_name'     => $user?->name,
        ]);
    }
}
