<?php

namespace Modules\Customer\Actions\Notes;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Customer\Models\CustomerNote;

class TogglePinNoteAction
{
    use AsAction;

    public function handle(CustomerNote $note): CustomerNote
    {
        $note->update(['is_pinned' => ! $note->is_pinned]);

        return $note->fresh();
    }
}
