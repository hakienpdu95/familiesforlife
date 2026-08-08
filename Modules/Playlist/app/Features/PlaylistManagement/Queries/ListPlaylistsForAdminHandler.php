<?php

namespace Modules\Playlist\Features\PlaylistManagement\Queries;

use App\Shared\Contracts\QueryHandlerInterface;
use App\Shared\Contracts\QueryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Modules\Playlist\Models\Playlist;

class ListPlaylistsForAdminHandler implements QueryHandlerInterface
{
    private const SORTABLE = ['name', 'sort_order', 'created_at'];

    public function handle(QueryInterface $query): LengthAwarePaginator
    {
        /** @var ListPlaylistsForAdminQuery $query */
        $sortField = in_array($query->sortField, self::SORTABLE, true) ? $query->sortField : 'sort_order';
        $sortDir = $query->sortDir === 'desc' ? 'desc' : 'asc';

        return Playlist::query()
            ->withCount('items')
            // eager-load để cột "Ảnh"/"Số item" ở Tabulator không N+1 (spec §7.4) — dùng
            // morphWith() build từ config, không hard-code Video::class/PostArticle::class.
            ->with(['items' => fn ($q) => $q->orderBy('sort_order')])
            ->with(['items.itemable' => function (MorphTo $morphTo): void {
                $morphTo->morphWith(
                    collect(config('playlist.itemables'))
                        ->mapWithKeys(fn (array $cfg) => [$cfg['model'] => $cfg['with']])
                        ->all()
                );
            }])
            ->when($query->search, fn ($q) => $q->where('name', 'like', '%'.$query->search.'%'))
            ->when($query->isActive !== null, fn ($q) => $q->where('is_active', $query->isActive))
            ->orderBy($sortField, $sortDir)
            ->orderBy('id')
            ->paginate($query->perPage, ['*'], 'page', $query->page)
            ->withQueryString();
    }
}
