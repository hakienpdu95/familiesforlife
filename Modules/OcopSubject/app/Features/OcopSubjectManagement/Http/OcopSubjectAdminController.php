<?php

namespace Modules\OcopSubject\Features\OcopSubjectManagement\Http;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\View\View;
use Modules\OcopSubject\Enums\OcopSubjectOrganizationType;
use Modules\OcopSubject\Features\OcopSubjectManagement\Actions\CreateOcopSubjectAction;
use Modules\OcopSubject\Features\OcopSubjectManagement\Actions\DeleteOcopSubjectAction;
use Modules\OcopSubject\Features\OcopSubjectManagement\Actions\StoreOcopSubjectDocumentsAction;
use Modules\OcopSubject\Features\OcopSubjectManagement\Actions\UpdateOcopSubjectAction;
use Modules\OcopSubject\Features\OcopSubjectManagement\Data\OcopSubjectData;
use Modules\OcopSubject\Features\OcopSubjectManagement\Http\Requests\OcopSubjectRequest;
use Modules\OcopSubject\Models\OcopSubject;

class OcopSubjectAdminController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(OcopSubject::class, 'ocop_subject');
    }

    public function index(): View
    {
        $types = OcopSubjectOrganizationType::cases();

        return view('ocopsubject::admin.index', compact('types'));
    }

    public function create(): View
    {
        $types = OcopSubjectOrganizationType::cases();

        return view('ocopsubject::admin.create', compact('types'));
    }

    public function store(OcopSubjectRequest $request, CreateOcopSubjectAction $action, StoreOcopSubjectDocumentsAction $storeDocuments): RedirectResponse
    {
        $validated = $request->validated();
        $ocopSubject = $action->handle(OcopSubjectData::from(Arr::except($validated, 'documents')));
        $storeDocuments->handle($ocopSubject, $validated['documents'] ?? []);

        return redirect()->route('backend.ocop-subjects.index')
            ->with('success', "Đã tạo chủ thể \"{$ocopSubject->name}\".");
    }

    public function edit(OcopSubject $ocopSubject): View
    {
        $types = OcopSubjectOrganizationType::cases();

        return view('ocopsubject::admin.edit', compact('ocopSubject', 'types'));
    }

    public function update(OcopSubjectRequest $request, OcopSubject $ocopSubject, UpdateOcopSubjectAction $action, StoreOcopSubjectDocumentsAction $storeDocuments): RedirectResponse
    {
        $validated = $request->validated();
        $action->handle($ocopSubject, OcopSubjectData::from(Arr::except($validated, 'documents')));
        $storeDocuments->handle($ocopSubject, $validated['documents'] ?? []);

        return redirect()->route('backend.ocop-subjects.index')
            ->with('success', 'Cập nhật chủ thể thành công.');
    }

    public function destroy(Request $request, OcopSubject $ocopSubject, DeleteOcopSubjectAction $action): RedirectResponse|JsonResponse
    {
        $action->handle($ocopSubject);

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Đã xoá chủ thể.']);
        }

        return redirect()->route('backend.ocop-subjects.index')
            ->with('success', 'Đã xoá chủ thể.');
    }
}
