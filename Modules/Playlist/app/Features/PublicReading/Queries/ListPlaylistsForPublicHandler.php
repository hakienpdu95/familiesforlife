<?php

namespace Modules\Playlist\Features\PublicReading\Queries;

use App\Shared\Contracts\QueryHandlerInterface;
use App\Shared\Contracts\QueryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Modules\Playlist\Models\Playlist;

/**
 * spec/Playlist_Technical_Specification.md §7.4 — trang /playlists cần CÙNG cấu trúc eager-load
 * như GetPlaylistForPublicHandler cho MỌI playlist trả về (không chỉ 1), vì
 * effective_cover_image_url gọi visible_itemables->first() — thiếu eager-load ở đây là nguồn N+1
 * dễ bị bỏ sót nhất (nhiều playlist × nhiều item mỗi cái).
 */
class ListPlaylistsForPublicHandler implements QueryHandlerInterface
{
    public function handle(QueryInterface $query): LengthAwarePaginator
    {
        /** @var ListPlaylistsForPublicQuery $query */
        return Playlist::active()
            ->with(['items' => fn ($q) => $q->orderBy('sort_order')])
            ->with(['items.itemable' => function (MorphTo $morphTo): void {
                $morphTo->morphWith(
                    collect(config('playlist.itemables'))
                        ->mapWithKeys(fn (array $cfg) => [$cfg['model'] => $cfg['with']])
                        ->all()
                );
            }])
            ->orderBy('sort_order')
            ->orderBy('id')
            ->paginate(config('playlist.per_page', 12), ['*'], 'page', $query->page);
    }
}
