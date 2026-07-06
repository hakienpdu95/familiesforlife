<?php

namespace Modules\KcItem\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Shared\Tenancy\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\KcItem\Models\KcItem;
use Modules\KcItem\Models\KcLearningProgress;

class KcProgressController extends Controller
{
    public function start(Request $request, KcItem $kcItem): RedirectResponse
    {
        $user  = $request->user();
        $orgId = TenantContext::getOrganizationId();

        KcLearningProgress::firstOrCreate(
            ['user_id' => $user->id, 'kc_item_id' => $kcItem->id],
            [
                'organization_id' => $orgId,
                'status'          => 'in_progress',
                'started_at'      => now(),
            ]
        );

        return back()->with('flash_success', 'Đã bắt đầu học tài liệu.');
    }

    public function complete(Request $request, KcItem $kcItem): RedirectResponse
    {
        $user  = $request->user();
        $orgId = TenantContext::getOrganizationId();

        KcLearningProgress::withoutTenant()
            ->updateOrCreate(
                ['user_id' => $user->id, 'kc_item_id' => $kcItem->id],
                [
                    'organization_id' => $orgId,
                    'status'          => 'completed',
                    'started_at'      => now(),
                    'completed_at'    => now(),
                ]
            );

        return back()->with('flash_success', 'Đã đánh dấu hoàn thành tài liệu.');
    }
}
