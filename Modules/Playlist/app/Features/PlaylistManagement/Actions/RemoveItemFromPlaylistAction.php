<?php

namespace Modules\Playlist\Features\PlaylistManagement\Actions;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Playlist\Models\PlaylistItem;

/**
 * spec/Playlist_Technical_Specification.md §5.2 — xoá cứng hàng playlist_items (đây chỉ là 1
 * liên kết, không phải nội dung gốc); LogsActivity trên PlaylistItem tự ghi lại sự kiện xoá.
 */
class RemoveItemFromPlaylistAction
{
    use AsAction;

    public function handle(PlaylistItem $item): void
    {
        $item->delete();
    }
}
