<?php

namespace Modules\Playlist\Features\PublicReading\Queries;

use App\Shared\Contracts\QueryHandlerInterface;
use App\Shared\Contracts\QueryInterface;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Modules\Playlist\Models\Playlist;

/**
 * spec/Playlist_Technical_Specification.md §7.4 — eager-load bắt buộc chống N+1. morphWith()
 * được build TỪ CONFIG ('playlist.itemables.*.with'), KHÔNG hard-code Video::class/
 * PostArticle::class ở đây — giữ nguyên nguyên tắc "không phụ thuộc cứng" (§0/§4.6) dù đang tối
 * ưu N+1 cho từng loại (Post cần 'translations' để PostArticle::default_locale_translation không
 * tự query lại).
 */
class GetPlaylistForPublicHandler implements QueryHandlerInterface
{
    public function handle(QueryInterface $query): ?Playlist
    {
        /** @var GetPlaylistForPublicQuery $query */
        return Playlist::active()
            ->where('slug', $query->slug)
            ->with(['items' => fn ($q) => $q->orderBy('sort_order')])
            ->with(['items.itemable' => function (MorphTo $morphTo): void {
                $morphTo->morphWith(
                    collect(config('playlist.itemables'))
                        ->mapWithKeys(fn (array $cfg) => [$cfg['model'] => $cfg['with']])
                        ->all()
                );
            }])
            ->first();
    }
}
