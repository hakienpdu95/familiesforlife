<?php

namespace Modules\Marketplace\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Shared\Tenancy\Models\Organization;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Marketplace\Actions\Backend\ApproveOrgAction;
use Modules\Marketplace\Actions\Backend\RejectOrgAction;
use Modules\Marketplace\Enums\ListingStatus;
use Modules\Marketplace\Enums\PosterType;
use Modules\Marketplace\Models\MktListing;

class OrgApprovalController extends Controller
{
    public function index(): View
    {
        // Admin only
        $this->authorize('marketplace.manage');

        $pending = MktListing::withoutTenant()
            ->select('mkt_listings.*')
            ->with(['organization.users' => fn($q) => $q->limit(1)])
            ->where('mkt_listings.status', ListingStatus::PENDING_REVIEW->value)
            ->where('mkt_listings.poster_type', PosterType::PENDING_ORG->value)
            ->latest('mkt_listings.created_at')
            ->paginate(20);

        return view('marketplace::org-approval.index', compact('pending'));
    }

    public function approve(Organization $org, ApproveOrgAction $action): RedirectResponse
    {
        $this->authorize('marketplace.manage');

        $action->handle($org, auth()->user());

        return back()->with('success', "Tổ chức \"{$org->name}\" đã được duyệt và các tin đăng đã active.");
    }

    public function reject(Request $request, Organization $org, RejectOrgAction $action): RedirectResponse
    {
        $this->authorize('marketplace.manage');

        $action->handle($org, $request->input('reason'));

        return back()->with('success', "Tổ chức \"{$org->name}\" đã bị từ chối.");
    }
}
