<?php

namespace Modules\PerformanceReview\Policies;

use App\Models\User;
use Modules\PerformanceReview\Models\PerformanceReview;

class PerformanceReviewPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['System_Admin', 'HR', 'CEO', 'Viewer', 'Ops']);
    }

    public function view(User $user, PerformanceReview $review): bool
    {
        return $user->hasAnyRole(['System_Admin', 'HR', 'CEO', 'Viewer', 'Ops']);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['System_Admin', 'HR']);
    }

    public function update(User $user, PerformanceReview $review): bool
    {
        if ($review->status?->value === 'finalized' || $review->status?->value === 'cancelled') {
            return false;
        }
        return $user->hasAnyRole(['System_Admin', 'HR']);
    }

    public function delete(User $user, PerformanceReview $review): bool
    {
        return $user->hasRole('System_Admin');
    }

    public function finalize(User $user, PerformanceReview $review): bool
    {
        return $user->hasAnyRole(['System_Admin', 'HR']);
    }
}
