<?php

namespace Modules\Lead\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Shared\Tenancy\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Lead\Actions\CreateTagAction;
use Modules\Lead\Actions\DeleteTagAction;
use Modules\Lead\Actions\UpdateTagAction;
use Modules\Lead\Data\Requests\StoreTagData;
use Modules\Lead\Models\LeadTagDefinition;
use Modules\Lead\Policies\LeadTagPolicy;
use Modules\Lead\Queries\ListTagsHandler;
use Modules\Lead\Queries\ListTagsQuery;

class LeadTagController extends Controller
{
    public function index(ListTagsHandler $handler): View
    {
        $this->authorize('viewAny', LeadTagDefinition::class);

        $tags = $handler->handle(new ListTagsQuery($this->orgId()));

        return view('lead::tags.index', compact('tags'));
    }

    public function store(Request $request, CreateTagAction $action): RedirectResponse
    {
        $this->authorize('create', LeadTagDefinition::class);

        $data = StoreTagData::validateAndCreate($request->all());

        try {
            $action->handle($data, $this->orgId());
            return redirect()->route('lead.tags.index')->with('success', 'Đã tạo tag thành công.');
        } catch (\InvalidArgumentException $e) {
            return back()->withErrors(['name' => $e->getMessage()])->withInput();
        }
    }

    public function edit(LeadTagDefinition $tag): View
    {
        $this->authorize('update', $tag);

        return view('lead::tags.edit', compact('tag'));
    }

    public function update(
        Request $request,
        LeadTagDefinition $tag,
        UpdateTagAction $action,
    ): RedirectResponse {
        $this->authorize('update', $tag);

        $data = StoreTagData::validateAndCreate($request->all());

        try {
            $action->handle($tag, $data);
            return redirect()->route('lead.tags.index')->with('success', 'Đã cập nhật tag.');
        } catch (\InvalidArgumentException $e) {
            return back()->withErrors(['name' => $e->getMessage()])->withInput();
        }
    }

    public function destroy(LeadTagDefinition $tag, DeleteTagAction $action): RedirectResponse
    {
        $this->authorize('delete', $tag);

        $action->handle($tag);

        return redirect()->route('lead.tags.index')->with('success', 'Đã xóa tag.');
    }

    private function orgId(): int
    {
        return TenantContext::getOrganizationId() ?? abort(403, 'No organization context.');
    }
}
