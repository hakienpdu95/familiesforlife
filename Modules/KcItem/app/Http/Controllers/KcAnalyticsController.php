<?php

namespace Modules\KcItem\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Shared\Tenancy\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\KcItem\Enums\KcItemStatus;
use Modules\KcItem\Models\KcItem;

class KcAnalyticsController extends Controller
{
    public function index()
    {
        $this->authorize('viewAny', KcItem::class);

        return view('kcitem::analytics');
    }

    public function topViewed(Request $request): JsonResponse
    {
        $this->authorize('viewAny', KcItem::class);

        $days  = (int) $request->input('days', 7);
        $days  = in_array($days, [7, 30]) ? $days : 7;
        $orgId = TenantContext::getOrganizationId();

        $items = DB::select(
            "SELECT kc_items.id, kc_items.uuid, kc_items.title, kc_items.type,
                    kc_items.view_count,
                    COUNT(kc_view_logs.id) as period_views
             FROM kc_items
             LEFT JOIN kc_view_logs
                    ON kc_view_logs.item_id = kc_items.id
                   AND kc_view_logs.viewed_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
             WHERE kc_items.organization_id = ?
               AND kc_items.status = ?
             GROUP BY kc_items.id, kc_items.uuid, kc_items.title, kc_items.type, kc_items.view_count
             ORDER BY period_views DESC
             LIMIT 10",
            [$days, $orgId, KcItemStatus::Approved->value]
        );

        return response()->json([
            'days'  => $days,
            'items' => array_map(fn ($row) => [
                'id'           => $row->id,
                'uuid'         => $row->uuid,
                'title'        => $row->title,
                'type'         => $row->type,
                'view_count'   => (int) $row->view_count,
                'period_views' => (int) $row->period_views,
                'url'          => route('backend.kc-items.show', $row->id),
            ], $items),
        ]);
    }

    public function byType(Request $request): JsonResponse
    {
        $this->authorize('viewAny', KcItem::class);

        $orgId = TenantContext::getOrganizationId();

        $rows = DB::select(
            "SELECT kc_items.type,
                    COUNT(*) as total,
                    AVG(kf.rating) as avg_rating
             FROM kc_items
             LEFT JOIN kc_feedbacks kf ON kf.item_id = kc_items.id
             WHERE kc_items.organization_id = ?
             GROUP BY kc_items.type",
            [$orgId]
        );

        return response()->json([
            'items' => array_map(fn ($row) => [
                'type'       => $row->type,
                'total'      => (int) $row->total,
                'avg_rating' => $row->avg_rating ? round((float) $row->avg_rating, 2) : null,
            ], $rows),
        ]);
    }

    public function expiringSoon(Request $request): JsonResponse
    {
        $this->authorize('viewAny', KcItem::class);

        $orgId = TenantContext::getOrganizationId();

        $rows = KcItem::where('organization_id', $orgId)
            ->where('status', KcItemStatus::Approved->value)
            ->whereNotNull('expired_date')
            ->whereBetween('expired_date', [now()->toDateString(), now()->addDays(30)->toDateString()])
            ->orderBy('expired_date')
            ->get(['id', 'uuid', 'title', 'type', 'expired_date']);

        return response()->json([
            'items' => $rows->map(function ($row) {
                $expiredDate = \Carbon\Carbon::parse($row->expired_date);
                return [
                    'id'           => $row->id,
                    'uuid'         => $row->uuid,
                    'title'        => $row->title,
                    'type'         => $row->type,
                    'expired_date' => $expiredDate->format('d/m/Y'),
                    'days_left'    => (int) now()->startOfDay()->diffInDays($expiredDate->startOfDay(), false),
                    'url'          => route('backend.kc-items.show', $row->id),
                ];
            })->values(),
        ]);
    }

    public function unread(Request $request): JsonResponse
    {
        $this->authorize('viewAny', KcItem::class);

        $orgId = TenantContext::getOrganizationId();

        $rows = DB::select(
            "SELECT kc_items.id, kc_items.uuid, kc_items.title, kc_items.type,
                    kc_items.created_at, u.name as owner_name
             FROM kc_items
             LEFT JOIN users u ON u.id = kc_items.owner_id
             WHERE kc_items.organization_id = ?
               AND kc_items.status = ?
               AND NOT EXISTS (
                   SELECT 1 FROM kc_view_logs
                   WHERE kc_view_logs.item_id = kc_items.id
               )
             ORDER BY kc_items.created_at DESC",
            [$orgId, KcItemStatus::Approved->value]
        );

        return response()->json([
            'items' => array_map(fn ($row) => [
                'id'         => $row->id,
                'uuid'       => $row->uuid,
                'title'      => $row->title,
                'type'       => $row->type,
                'created_at' => \Carbon\Carbon::parse($row->created_at)->format('d/m/Y'),
                'owner'      => $row->owner_name,
                'url'        => route('backend.kc-items.show', $row->id),
            ], $rows),
        ]);
    }
}
