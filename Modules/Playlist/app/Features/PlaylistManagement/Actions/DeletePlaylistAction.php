<?php

namespace Modules\Playlist\Features\PlaylistManagement\Actions;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Playlist\Models\Playlist;

class DeletePlaylistAction
{
    use AsAction;

    /** Soft-delete — playlist_items liên quan vẫn còn nguyên (§0 — cascadeOnDelete chỉ chạy khi forceDelete()). */
    public function handle(Playlist $playlist): void
    {
        $playlist->delete();
    }
}
