<?php

namespace Modules\OcopSubject\Features\OcopSubjectManagement\Http;

use App\Http\Controllers\Controller;
use App\Models\Province;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\View\View;
use Modules\OcopSubject\Enums\OcopSubjectOrganizationType;
use Modules\OcopSubject\Features\OcopSubjectManagement\Actions\CreateOcopSubjectAction;
use Modules\OcopSubject\Features\OcopSubjectManagement\Actions\DeleteOcopSubjectAction;
use Modules\OcopSubject\Features\OcopSubjectManagement\Actions\ImportOcopSubjectsAction;
use Modules\OcopSubject\Features\OcopSubjectManagement\Actions\StoreOcopSubjectDocumentsAction;
use Modules\OcopSubject\Features\OcopSubjectManagement\Actions\UpdateOcopSubjectAction;
use Modules\OcopSubject\Features\OcopSubjectManagement\Data\OcopSubjectData;
use Modules\OcopSubject\Features\OcopSubjectManagement\Http\Requests\OcopSubjectImportRequest;
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
        $provinces = Province::where('is_active', true)->orderBy('name')->get(['province_code', 'name']);

        return view('ocopsubject::admin.index', compact('types', 'provinces'));
    }

    public function import(OcopSubjectImportRequest $request, ImportOcopSubjectsAction $action): RedirectResponse
    {
        $this->authorize('create', OcopSubject::class);

        $result = $action->handle($request->file('file')->getRealPath(), $request->validated('province_code'));

        return redirect()->route('backend.ocop-subjects.index')
            ->with('success', "Import xong: thêm mới {$result['created']} chủ thể, bỏ qua {$result['duplicates']} chủ thể đã tồn tại, ".count($result['errors']).' dòng lỗi.')
            ->with('import_errors', $result['errors']);
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
