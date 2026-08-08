<?php

namespace Modules\Playlist\Features\PublicReading\Http;

use App\Http\Controllers\Controller;
use Illuminate\View\View;
use Modules\Playlist\Features\PublicReading\Queries\GetPlaylistForPublicHandler;
use Modules\Playlist\Features\PublicReading\Queries\GetPlaylistForPublicQuery;
use Modules\Playlist\Features\PublicReading\Queries\ListPlaylistsForPublicHandler;
use Modules\Playlist\Features\PublicReading\Queries\ListPlaylistsForPublicQuery;

/**
 * spec/Playlist_Technical_Specification.md §7 — /playlists (danh sách) + /playlists/{slug}
 * (chi tiết, card polymorphic qua PlaylistableContract, SEO đầy đủ).
 */
class PlaylistPublicController extends Controller
{
    public function index(ListPlaylistsForPublicHandler $handler): View
    {
        $playlists = $handler->handle(new ListPlaylistsForPublicQuery(
            page: (int) request('page', 1),
        ));

        return view('playlist::public.index', compact('playlists'));
    }

    public function show(string $slug, GetPlaylistForPublicHandler $handler): View
    {
        $playlist = $handler->handle(new GetPlaylistForPublicQuery($slug));

        abort_unless($playlist, 404);

        $canonicalUrl = route('playlist.public.show', $playlist);

        return view('playlist::public.show', compact('playlist', 'canonicalUrl'));
    }
}
