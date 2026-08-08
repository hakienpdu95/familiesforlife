<?php

namespace Modules\Playlist\Policies;

use App\Models\User;
use Modules\Playlist\Models\Playlist;

/**
 * spec/Playlist_Technical_Specification.md §0 — 1 permission duy nhất (playlist.manage), RIÊNG
 * khỏi video.manage dù Playlist đọc dữ liệu Video — Playlist là module cross-cutting mới, mượn
 * permission của Video sẽ khiến 1 tài khoản chỉ cần quản video lại vô tình quản được cả playlist
 * chứa bài viết. Gán cho platform_ops + platform_content_head (PlaylistPermissionSeeder).
 */
class PlaylistPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('playlist.manage');
    }

    public function view(User $user, Playlist $playlist): bool
    {
        return $user->can('playlist.manage');
    }

    public function create(User $user): bool
    {
        return $user->can('playlist.manage');
    }

    public function update(User $user, Playlist $playlist): bool
    {
        return $user->can('playlist.manage');
    }

    public function delete(User $user, Playlist $playlist): bool
    {
        return $user->can('playlist.manage');
    }
}
