<?php

namespace Modules\Video\Policies;

use App\Models\User;
use Modules\Video\Models\Video;

/**
 * spec/Video_Management_Technical_Specification.md §6.7 — 1 permission duy nhất
 * (video.manage), gán cho platform_ops + platform_content_head (VideoPermissionSeeder).
 */
class VideoPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('video.manage');
    }

    public function view(User $user, Video $video): bool
    {
        return $user->can('video.manage');
    }

    public function create(User $user): bool
    {
        return $user->can('video.manage');
    }

    public function update(User $user, Video $video): bool
    {
        return $user->can('video.manage');
    }

    public function delete(User $user, Video $video): bool
    {
        return $user->can('video.manage');
    }
}
