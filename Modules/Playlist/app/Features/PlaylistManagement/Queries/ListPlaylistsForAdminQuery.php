<?php

namespace Modules\Playlist\Features\PlaylistManagement\Queries;

use App\Shared\Contracts\QueryInterface;

class ListPlaylistsForAdminQuery implements QueryInterface
{
    public function __construct(
        public readonly ?string $search = null,
        public readonly ?bool $isActive = null,
        public readonly int $page = 1,
        public readonly int $perPage = 20,
        public readonly string $sortField = 'sort_order',
        public readonly string $sortDir = 'asc',
    ) {}
}
