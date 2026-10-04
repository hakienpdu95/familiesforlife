<?php

namespace Modules\OcopSubject\Policies;

use App\Models\User;
use Modules\OcopSubject\Models\OcopSubject;

class OcopSubjectPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('ocop_subject.manage');
    }

    public function view(User $user, OcopSubject $ocopSubject): bool
    {
        return $user->can('ocop_subject.manage');
    }

    public function create(User $user): bool
    {
        return $user->can('ocop_subject.manage');
    }

    public function update(User $user, OcopSubject $ocopSubject): bool
    {
        return $user->can('ocop_subject.manage');
    }

    public function delete(User $user, OcopSubject $ocopSubject): bool
    {
        return $user->can('ocop_subject.manage');
    }
}
