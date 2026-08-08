<?php

namespace Modules\Playlist\Features\PlaylistManagement\Actions;

use Illuminate\Support\Collection;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Playlist\Contracts\PlaylistableContract;
use Modules\Playlist\Models\Playlist;

/**
 * spec/Playlist_Technical_Specification.md §6.4 — trái tim của ô tìm kiếm hợp nhất. Gọi lần
 * lượt từng nguồn khai trong config('playlist.itemables') qua chính
 * scopeSearchablePlaylistItems() mà PlaylistableContract bắt buộc (§4.3) — Action này KHÔNG viết
 * SQL trực tiếp lên bảng cụ thể, chỉ điều phối + chuẩn hoá + lọc kết quả.
 */
class SearchPlaylistableItemsAction
{
    use AsAction;

    /**
     * $playlist (nullable): nếu truyền vào, loại khỏi kết quả các (type,id) ĐÃ có trong playlist
     * đó — tránh hiển thị trong picker 1 item mà bấm vào sẽ không thêm được nữa.
     *
     * @return Collection<int, array{type: string, id: int, title: string, thumbnail_url: ?string, type_label: string}>
     */
    public function handle(string $keyword, ?Playlist $playlist = null): Collection
    {
        $limit = config('playlist.search_limit_per_type', 10);

        $existingKeys = $playlist
            ? $playlist->items->map(fn ($item) => "{$item->itemable_type}:{$item->itemable_id}")
            : collect();

        $results = collect();

        foreach (config('playlist.itemables', []) as $typeKey => $cfg) {
            /** @var class-string<PlaylistableContract> $modelClass */
            $modelClass = $cfg['model'];

            // Eager-load 'with' khai trong config (§4.6/§7.4) TRƯỚC KHI gọi getPlaylistCard*()
            // bên dưới — thiếu dòng này gây lỗi ẩn nghiêm trọng phát hiện sau review: Eloquent
            // (Model::shouldBeStrict()) chỉ chặn lazy-load khi kết quả trả về NHIỀU HƠN 1 dòng
            // (Builder::hydrate(): `if (count($items) > 1) preventsLazyLoading = true`) — nên
            // tìm kiếm khớp ĐÚNG 1 bản ghi vẫn chạy được (không bị chặn), nhưng khớp ≥2 bản ghi
            // (trường hợp thực tế phổ biến nhất) ném LazyLoadingViolationException khi
            // getPlaylistCardTitle() chạm `translations` chưa eager-load → request 500 → JS coi
            // như "không có kết quả" (âm thầm nuốt lỗi ở phía client, §8 nên có test riêng).
            $matches = $modelClass::query()
                ->searchablePlaylistItems($keyword)
                ->with($cfg['with'])
                ->limit($limit)
                ->get();

            foreach ($matches as $item) {
                // Phòng thủ lớp 2 (§0) — dù scope DB thường đã lọc active/published tương tự,
                // kiểm tra lại qua contract để không lệch nếu 1 trong 2 nơi đổi điều kiện sau này.
                if (! $item->isPlaylistCardVisible()) {
                    continue;
                }

                if ($existingKeys->contains("{$typeKey}:{$item->getKey()}")) {
                    continue;
                }

                $results->push([
                    'type' => $typeKey,
                    'id' => $item->getKey(),
                    'title' => $item->getPlaylistCardTitle(),
                    'thumbnail_url' => $item->getPlaylistCardThumbnailUrl(),
                    'type_label' => $item->getPlaylistCardTypeLabel(),
                ]);
            }
        }

        return $results;
    }
}
