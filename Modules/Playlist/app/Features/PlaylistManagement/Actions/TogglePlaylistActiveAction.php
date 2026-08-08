<?php

namespace Modules\Playlist\Features\PlaylistManagement\Actions;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Playlist\Models\Playlist;

class TogglePlaylistActiveAction
{
    use AsAction;

    public function handle(Playlist $playlist): Playlist
    {
        $playlist->update([
            'is_active' => ! $playlist->is_active,
            'updated_by' => auth()->id(),
        ]);

        return $playlist->fresh();
    }
}
