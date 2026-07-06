<?php

namespace Modules\Lead\Actions\Notes;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Lead\Models\LeadNote;

class TogglePinNoteAction
{
    use AsAction;

    public function handle(LeadNote $note): LeadNote
    {
        $note->update(['is_pinned' => ! $note->is_pinned]);

        return $note->fresh();
    }
}
