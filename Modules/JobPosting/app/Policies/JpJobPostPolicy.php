<?php

namespace Modules\JobPosting\Policies;

use App\Models\User;
use Modules\JobPosting\Models\JpJobPost;

class JpJobPostPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyPermission(['job_posting.view', 'job_posting.manage']);
    }

    public function view(User $user, JpJobPost $post): bool
    {
        return $user->hasAnyPermission(['job_posting.view', 'job_posting.manage'])
            && $user->organization_id === $post->organization_id;
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('job_posting.create');
    }

    public function update(User $user, JpJobPost $post): bool
    {
        return $user->hasAnyPermission(['job_posting.edit', 'job_posting.manage'])
            && $user->organization_id === $post->organization_id;
    }

    public function delete(User $user, JpJobPost $post): bool
    {
        return $user->hasPermissionTo('job_posting.manage')
            && $user->organization_id === $post->organization_id;
    }

    public function publish(User $user, JpJobPost $post): bool
    {
        return $user->hasPermissionTo('job_posting.publish')
            && $user->organization_id === $post->organization_id;
    }

    public function close(User $user, JpJobPost $post): bool
    {
        return $user->hasAnyPermission(['job_posting.edit', 'job_posting.manage'])
            && $user->organization_id === $post->organization_id;
    }
}
