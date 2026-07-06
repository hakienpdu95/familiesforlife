<?php

namespace Modules\Customer\Actions\Notes;

use Illuminate\Support\Facades\Auth;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Customer\Data\Requests\StoreNoteData;
use Modules\Customer\Models\Customer;
use Modules\Customer\Models\CustomerNote;

class StoreNoteAction
{
    use AsAction;

    public function handle(Customer $customer, StoreNoteData $data): CustomerNote
    {
        $user = Auth::user();

        return CustomerNote::create([
            'customer_id'     => $customer->id,
            'organization_id' => $customer->organization_id,
            'content'         => $data->content,
            'is_pinned'       => false,
            'author_id'       => $user?->id,
            'author_name'     => $user?->name,
        ]);
    }
}
