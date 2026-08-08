<?php

namespace Modules\Playlist\Features\PlaylistManagement\Actions;

use Illuminate\Validation\ValidationException;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Playlist\Contracts\PlaylistableContract;
use Modules\Playlist\Models\Playlist;
use Modules\Playlist\Models\PlaylistItem;

/**
 * spec/Playlist_Technical_Specification.md §0/§5.1.
 */
class AddItemToPlaylistAction
{
    use AsAction;

    public function handle(Playlist $playlist, string $itemableType, int $itemableId): PlaylistItem
    {
        $modelClass = config("playlist.itemables.{$itemableType}.model");

        if (! $modelClass) {
            throw ValidationException::withMessages(['itemable_type' => 'Loại nội dung không hợp lệ.']);
        }

        /** @var PlaylistableContract|null $itemable */
        $itemable = $modelClass::find($itemableId);

        // Re-check isPlaylistCardVisible() ngay tại thời điểm ghi — KHÔNG tin dữ liệu client gửi
        // lên (picker có thể đã trả kết quả cũ, item vừa bị tắt/unpublish ngay trước khi request
        // này tới server, §8 "race search→attach").
        if (! $itemable instanceof PlaylistableContract || ! $itemable->isPlaylistCardVisible()) {
            throw ValidationException::withMessages([
                'itemable_id' => 'Nội dung này hiện đang ẩn hoặc chưa xuất bản, không thể thêm vào playlist.',
            ]);
        }

        // Mặc định "thêm vào cuối danh sách" — KHÔNG bắt nhập sort_order lúc thêm mới (§0).
        // Sắp xếp lại vị trí là thao tác riêng, sau đó, qua ReorderPlaylistItemsAction.
        $nextSortOrder = ($playlist->items()->max('sort_order') ?? 0) + 1;

        return $playlist->items()->create([
            'itemable_type' => $itemableType,
            'itemable_id' => $itemableId,
            'sort_order' => $nextSortOrder,
        ]);
    }
}
