<?php

namespace Modules\Customer\Actions\Notes;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Customer\Data\Requests\StoreNoteData;
use Modules\Customer\Models\CustomerNote;

class UpdateNoteAction
{
    use AsAction;

    public function handle(CustomerNote $note, StoreNoteData $data): CustomerNote
    {
        $note->update(['content' => $data->content]);

        return $note->fresh();
    }
}
