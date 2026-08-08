<?php

namespace Modules\Playlist\Providers;

use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Gate;
use Modules\Playlist\Models\Playlist;
use Modules\Playlist\Policies\PlaylistPolicy;
use Nwidart\Modules\Support\ModuleServiceProvider;

class PlaylistServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'Playlist';

    protected string $nameLower = 'playlist';

    protected array $providers = [
        RouteServiceProvider::class,
    ];

    public function boot(): void
    {
        parent::boot();

        Gate::policy(Playlist::class, PlaylistPolicy::class);

        // spec/Playlist_Technical_Specification.md §0/§4.7 — cùng quyết định
        // ApprovalServiceProvider::boot(): merge: true, KHÔNG enforceMorphMap() (1 module con
        // không có thẩm quyền bật cờ toàn cục "mọi polymorphic relation của cả app phải có
        // trong morph map" — xem Modules/Approval/app/Providers/ApprovalServiceProvider.php).
        Relation::morphMap(
            collect(config('playlist.itemables', []))->map(fn (array $cfg) => $cfg['model'])->all(),
            merge: true,
        );
    }
}
