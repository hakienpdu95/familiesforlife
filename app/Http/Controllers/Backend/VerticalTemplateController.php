<?php

namespace App\Http\Controllers\Backend;

use App\Foundation\Vertical\VerticalTemplate;
use App\Http\Controllers\Controller;
use App\Http\Requests\VerticalTemplateRequest;
use App\Shared\Tenancy\Models\Organization;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Survey\Models\Survey;

/**
 * System Admin — quản lý thư viện mẫu vertical (organization_id = null).
 * Bản instance riêng của từng tổ chức quản lý qua
 * Modules\Organization\Http\Controllers\OrganizationVerticalController, không qua đây.
 */
class VerticalTemplateController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(VerticalTemplate::class, 'vertical_template');
    }

    public function index(): View
    {
        $templates = VerticalTemplate::whereNull('organization_id')
            ->withCount(['phases', 'clones'])
            ->orderBy('label')
            ->get();

        return view('backend.vertical-templates.index', compact('templates'));
    }

    public function create(): View
    {
        $organizations = Organization::orderBy('name')->get(['id', 'name']);

        return view('backend.vertical-templates.create', compact('organizations'));
    }

    public function store(VerticalTemplateRequest $request): RedirectResponse
    {
        $template = VerticalTemplate::create(array_merge($request->toData(), [
            'organization_id' => $request->input('organization_id') ?: null,
            'status'          => 'active',
            'sidebar_config'  => [],
        ]));

        return redirect()
            ->route('backend.vertical-templates.edit', $template)
            ->with('success', "Đã tạo bản mẫu \"{$template->label}\". Thêm phase/checklist để hoàn thiện.");
    }

    public function edit(VerticalTemplate $verticalTemplate): View
    {
        return view('backend.vertical-templates.edit', [
            'template'   => $verticalTemplate,
            'phasesData' => $verticalTemplate->toBuilderPhasesData(),
        ]);
    }

    public function update(VerticalTemplateRequest $request, VerticalTemplate $verticalTemplate): RedirectResponse
    {
        $verticalTemplate->update($request->toData());

        return redirect()
            ->route('backend.vertical-templates.edit', $verticalTemplate)
            ->with('success', 'Đã cập nhật thông tin bản mẫu.');
    }

    public function destroy(VerticalTemplate $verticalTemplate): RedirectResponse
    {
        $label = $verticalTemplate->label;
        $verticalTemplate->delete();

        return redirect()
            ->route('backend.vertical-templates.index')
            ->with('success', "Đã xóa bản mẫu \"{$label}\".");
    }

    /**
     * AJAX: danh sách slug khảo sát (surveys.slug) cho 2 select readiness_template_slug /
     * data_collection_template_slug — không có field phân loại readiness vs data-collection
     * trong bảng surveys nên cả 2 select dùng chung 1 nguồn danh sách.
     * Lọc CHẶT theo organization_id đã chọn — chỉ survey thuộc đúng tổ chức đó, không
     * gộp thêm survey dùng chung (organization_id null). Không chọn tổ chức → không trả gì
     * (frontend cũng không gọi endpoint này khi chưa chọn tổ chức).
     */
    public function surveyOptions(Request $request): JsonResponse
    {
        $this->authorize('viewAny', VerticalTemplate::class);

        $orgId = $request->integer('organization_id') ?: null;
        $q     = trim((string) $request->input('q', ''));

        if (! $orgId) {
            return response()->json([]);
        }

        $rows = Survey::active()
            ->where('organization_id', $orgId)
            ->when($q, fn ($query) => $query->where('title', 'like', "%{$q}%"))
            ->orderBy('title')
            ->get(['id', 'slug', 'title']);

        return response()->json($rows->map(fn ($s) => [
            'id'   => $s->slug,
            'text' => $s->title,
        ]));
    }
}
