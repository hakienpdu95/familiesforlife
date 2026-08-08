<?php

namespace Modules\Playlist\Features\PublicReading\Queries;

use App\Shared\Contracts\QueryInterface;

class GetPlaylistForPublicQuery implements QueryInterface
{
    public function __construct(
        public readonly string $slug,
    ) {}
}
