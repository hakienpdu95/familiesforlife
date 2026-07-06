<?php

namespace Modules\Marketplace\Http\Controllers\Api\Org;

use App\Http\Controllers\Controller;
use App\Shared\Tenancy\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Modules\Marketplace\Enums\ApplicationStatus;
use Modules\Marketplace\Enums\JpSyncStatus;
use Modules\Marketplace\Enums\ListingStatus;
use Modules\Marketplace\Models\MktApplication;
use Modules\Marketplace\Models\MktListing;

class OrgAnalyticsController extends Controller
{
    public function dashboard(): JsonResponse
    {
        $orgId    = TenantContext::getOrganizationId();
        $cacheKey = 'mkt:org:' . $orgId . ':dashboard';

        $data = Cache::remember($cacheKey, 180, function () use ($orgId) {
            $listingStats = MktListing::withoutGlobalScope('tenant')
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

            $pendingApplications = MktApplication::whereHas('listing', fn($q) =>
                    $q->withoutGlobalScope('tenant')->where('org_id', $orgId)
                )
                ->whereNotIn('status', [
                    ApplicationStatus::Rejected->value,
                    ApplicationStatus::Withdrawn->value,
                    ApplicationStatus::Hired->value,
                ])
                ->count();

            $hiredCount = MktApplication::whereHas('listing', fn($q) =>
                    $q->withoutGlobalScope('tenant')->where('org_id', $orgId)
                )
                ->where('status', ApplicationStatus::Hired->value)
                ->count();

            $listingBreakdown = MktListing::withoutGlobalScope('tenant')
                ->where('org_id', $orgId)
                ->selectRaw('
                    id, uuid, title, slug, status, listing_type,
                    view_count, application_count, bookmark_count, jp_sync_status,
                    CASE WHEN view_count > 0 THEN ROUND(application_count * 100.0 / view_count, 1) ELSE 0 END as conversion_rate
                ')
                ->orderByDesc('created_at')
                ->get();

            return [
                'summary' => [
                    'total_listings'      => (int) ($listingStats->total_listings ?? 0),
                    'active_listings'     => (int) ($listingStats->active_listings ?? 0),
                    'total_views'         => (int) ($listingStats->total_views ?? 0),
                    'total_applications'  => (int) ($listingStats->total_applications ?? 0),
                    'total_bookmarks'     => (int) ($listingStats->total_bookmarks ?? 0),
                    'total_hired'         => $hiredCount,
                    'pending_applications' => $pendingApplications,
                    'out_of_sync_count'   => (int) ($listingStats->out_of_sync_count ?? 0),
                ],
                'listings' => $listingBreakdown->map(fn($l) => [
                    'id'              => $l->uuid,
                    'title'           => $l->title,
                    'slug'            => $l->slug,
                    'status'          => $l->status,
                    'listing_type'    => $l->listing_type,
                    'view_count'      => $l->view_count,
                    'application_count' => $l->application_count,
                    'bookmark_count'  => $l->bookmark_count,
                    'conversion_rate' => (float) $l->conversion_rate,
                    'jp_sync_status'  => $l->jp_sync_status,
                ]),
            ];
        });

        return response()->json($data);
    }

    public function syncStatus(): JsonResponse
    {
        $orgId = TenantContext::getOrganizationId();

        $count = MktListing::withoutGlobalScope('tenant')
            ->where('org_id', $orgId)
            ->where('jp_sync_status', JpSyncStatus::OUT_OF_SYNC->value)
            ->count();

        return response()->json(['out_of_sync_count' => $count]);
    }
}
