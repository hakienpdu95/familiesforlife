<?php

namespace Modules\Customer\Actions\Notes;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Customer\Models\CustomerNote;

class DestroyNoteAction
{
    use AsAction;

    public function handle(CustomerNote $note): void
    {
        $note->delete();
    }
}
