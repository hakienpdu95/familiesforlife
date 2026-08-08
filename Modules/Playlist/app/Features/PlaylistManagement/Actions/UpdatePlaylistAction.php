<?php

namespace Modules\Playlist\Features\PlaylistManagement\Actions;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Playlist\Features\PlaylistManagement\Data\PlaylistData;
use Modules\Playlist\Models\Playlist;

class UpdatePlaylistAction
{
    use AsAction;

    public function handle(Playlist $playlist, PlaylistData $data): Playlist
    {
        $playlist->update([
            'name' => $data->name,
            'slug' => $data->slug,
            'description' => $data->description,
            'cover_image_url' => $data->cover_image_url,
            'meta_title' => $data->meta_title,
            'meta_description' => $data->meta_description,
            'sort_order' => $data->sort_order,
            'is_active' => $data->is_active,
            'updated_by' => auth()->id(),
        ]);

        return $playlist->fresh();
    }
}
