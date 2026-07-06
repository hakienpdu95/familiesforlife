<?php

namespace Modules\Marketplace\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Shared\Tenancy\TenantContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;
use Modules\Marketplace\Enums\ApplicationStatus;
use Modules\Marketplace\Enums\JpSyncStatus;
use Modules\Marketplace\Enums\ListingStatus;
use Modules\Marketplace\Models\MktApplication;
use Modules\Marketplace\Models\MktListing;

class MarketplaceAnalyticsController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', MktListing::class);

        $orgId    = TenantContext::getOrganizationId();
        $cacheKey = 'mkt:org:' . $orgId . ':dashboard';

        $analytics = Cache::remember($cacheKey, 180, function () use ($orgId) {
            $stats = MktListing::withoutGlobalScope('tenant')
                ->where('org_id', $orgId)
                ->selectRaw('
                    COUNT(*) as total_listings,
                    SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as active_listings,
                    SUM(view_count) as total_views,
                    SUM(application_count) as total_applications,
                    SUM(bookmark_count) as total_bookmarks,
                    SUM(CASE WHEN jp_sync_status = ? THEN 1 ELSE 0 END) as out_of_sync_count
                ', [ListingStatus::ACTIVE->value, JpSyncStatus::OUT_OF_SYNC->value])
                ->first();

            $hired = MktApplication::whereHas('listing', fn($q) =>
                    $q->withoutGlobalScope('tenant')->where('org_id', $orgId)
                )
                ->where('status', ApplicationStatus::Hired->value)
                ->count();

            $pending = MktApplication::whereHas('listing', fn($q) =>
                    $q->withoutGlobalScope('tenant')->where('org_id', $orgId)
                )
                ->whereNotIn('status', [
                    ApplicationStatus::Rejected->value,
                    ApplicationStatus::Withdrawn->value,
                    ApplicationStatus::Hired->value,
                ])
                ->count();

            $listings = MktListing::withoutGlobalScope('tenant')
                ->where('org_id', $orgId)
                ->orderByDesc('created_at')
                ->get()
                ->map(fn($l) => [
                    'model'           => $l,
                    'conversion_rate' => $l->view_count > 0
                        ? round($l->application_count / $l->view_count * 100, 1)
                        : 0,
                ]);

            return compact('stats', 'hired', 'pending', 'listings');
        });

        return view('marketplace::analytics.index', $analytics);
    }
}
