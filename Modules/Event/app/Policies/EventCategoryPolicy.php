<?php

namespace Modules\Event\Policies;

use App\Models\User;
use Modules\Event\Models\EventCategory;

class EventCategoryPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('event.view');
    }

    public function view(User $user, EventCategory $eventCategory): bool
    {
        return $user->can('event.view');
    }

    public function create(User $user): bool
    {
        return $user->can('event_category.manage');
    }

    public function update(User $user, EventCategory $eventCategory): bool
    {
        return $user->can('event_category.manage');
    }

    public function delete(User $user, EventCategory $eventCategory): bool
    {
        return $user->can('event_category.manage');
    }
}
