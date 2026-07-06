<?php

namespace Modules\Lead\Actions\Notes;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Lead\Models\LeadNote;

class DestroyNoteAction
{
    use AsAction;

    public function handle(LeadNote $note): void
    {
        $note->delete();
    }
}
