<?php

namespace Modules\Assessment\Policies;

use App\Models\User;
use Modules\Assessment\Models\PassportEntry;

class PassportEntryPolicy
{
    public function view(User $user, PassportEntry $entry): bool
    {
        return $user->id === $entry->user_id;
    }

    public function update(User $user, PassportEntry $entry): bool
    {
        return $user->id === $entry->user_id;
    }
}
