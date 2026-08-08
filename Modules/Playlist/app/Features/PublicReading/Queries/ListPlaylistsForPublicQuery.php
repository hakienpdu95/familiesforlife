<?php

namespace Modules\Playlist\Features\PublicReading\Queries;

use App\Shared\Contracts\QueryInterface;

class ListPlaylistsForPublicQuery implements QueryInterface
{
    public function __construct(
        public readonly int $page = 1,
    ) {}
}
