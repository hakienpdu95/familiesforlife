<?php

namespace Modules\Lead\Actions\Notes;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Lead\Data\Requests\StoreNoteData;
use Modules\Lead\Models\LeadNote;

class UpdateNoteAction
{
    use AsAction;

    public function handle(LeadNote $note, StoreNoteData $data): LeadNote
    {
        $note->update(['content' => $data->content]);

        return $note->fresh();
    }
}
