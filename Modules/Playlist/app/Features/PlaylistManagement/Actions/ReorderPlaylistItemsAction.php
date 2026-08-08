<?php

namespace Modules\Playlist\Features\PlaylistManagement\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Playlist\Models\Playlist;

/**
 * spec/Playlist_Technical_Specification.md §5.2 — cập nhật sort_order hàng loạt theo thứ tự
 * mảng truyền vào. Validate MỌI ID trong mảng đều thuộc đúng $playlist trước khi update — chặn
 * 1 ID của playlist khác lọt vào request thủ công, không âm thầm bỏ qua hay cập nhật nhầm
 * playlist khác (§8).
 */
class ReorderPlaylistItemsAction
{
    use AsAction;

    /** @param array<int, int> $orderedItemIds */
    public function handle(Playlist $playlist, array $orderedItemIds): void
    {
        $ownedItemIds = $playlist->items()->pluck('id');

        $unknownIds = array_diff($orderedItemIds, $ownedItemIds->all());

        if ($unknownIds !== []) {
            throw ValidationException::withMessages([
                'ordered_item_ids' => 'Danh sách sắp xếp chứa item không thuộc playlist này.',
            ]);
        }

        DB::transaction(function () use ($orderedItemIds): void {
            foreach (array_values($orderedItemIds) as $index => $itemId) {
                DB::table('playlist_items')->where('id', $itemId)->update(['sort_order' => $index + 1]);
            }
        });
    }
}
