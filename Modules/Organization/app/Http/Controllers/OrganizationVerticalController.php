<?php

namespace Modules\Organization\Http\Controllers;

use App\Foundation\Vertical\CloneVerticalFromTemplateAction;
use App\Foundation\Vertical\CreateVerticalFromScratchAction;
use App\Foundation\Vertical\DeactivateVerticalAction;
use App\Foundation\Vertical\VerticalConfigItem;
use App\Foundation\Vertical\VerticalTemplate;
use App\Foundation\VerticalRegistry;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Modules\Organization\Models\Organization;

class OrganizationVerticalController extends Controller
{
    // ── Index — danh sách tất cả vertical templates, trạng thái per org ───────

    public function index(Organization $organization): View
    {
        $this->authorize('update', $organization);

        $templates = VerticalRegistry::libraryTemplates();

        $activated = VerticalTemplate::where('organization_id', $organization->id)
            ->with('phases')
            ->get()
            ->keyBy('code');

        // Bản tự tạo từ đầu ("Tạo mới từ đầu") — không tương ứng bản mẫu thư viện nào,
        // nên không xuất hiện trong lưới $templates ở trên, phải liệt kê riêng.
        $custom = $activated->reject(fn ($ov) => $templates->contains('code', $ov->code))->values();

        return view('organization::verticals.index', compact('organization', 'templates', 'activated', 'custom'));
    }

    // ── Tạo mới từ đầu — instance trống, không nhân bản thư viện ─────────────

    public function createFromScratch(Organization $organization): View
    {
        $this->authorize('update', $organization);

        return view('organization::verticals.create', compact('organization'));
    }

    public function storeFromScratch(Request $request, Organization $organization): RedirectResponse
    {
        $this->authorize('update', $organization);

        $validated = $request->validate([
            'code'                           => ['required', 'string', 'max:50', 'regex:/^[a-z0-9]+(-[a-z0-9]+)*$/'],
            'label'                          => ['required', 'string', 'max:100'],
            'target_label'                   => ['required', 'string', 'max:50'],
            'target_org_category'           => ['required', 'string', 'max:30'],
            'has_physical_assets'           => ['boolean'],
            'readiness_template_slug'       => ['nullable', 'string', 'max:100'],
            'data_collection_template_slug' => ['nullable', 'string', 'max:100'],
        ], [
            'code.required'                 => 'Vui lòng nhập mã vertical.',
            'code.regex'                    => 'Mã vertical chỉ gồm chữ thường, số, dấu gạch ngang (vd: quan-ly-kho).',
            'label.required'                => 'Vui lòng nhập tên hiển thị.',
            'target_label.required'         => 'Vui lòng nhập nhãn đối tượng triển khai.',
            'target_org_category.required'  => 'Vui lòng nhập nhóm đối tượng.',
        ]);

        if (VerticalTemplate::where('organization_id', $organization->id)->where('code', $validated['code'])->exists()) {
            return back()->withErrors(['code' => 'Tổ chức đã có vertical với mã này.'])->withInput();
        }

        $vertical = (new CreateVerticalFromScratchAction)->execute(
            $organization->id,
            $validated['code'],
            $validated['label'],
            [
                'target_label'                   => $validated['target_label'],
                'target_org_category'           => $validated['target_org_category'],
                'has_physical_assets'           => $request->boolean('has_physical_assets'),
                'readiness_template_slug'       => $validated['readiness_template_slug'] ?? null,
                'data_collection_template_slug' => $validated['data_collection_template_slug'] ?? null,
            ]
        );

        return redirect()
            ->route('backend.organizations.verticals.config', [$organization, $vertical->code])
            ->with('success', "Đã tạo vertical \"{$vertical->label}\". Thêm phase/checklist bên dưới để hoàn thiện.");
    }

    // ── Activate ──────────────────────────────────────────────────────────────

    public function activate(Request $request, Organization $organization, string $code): RedirectResponse
    {
        $this->authorize('update', $organization);

        (new CloneVerticalFromTemplateAction)->execute($organization->id, $code);

        return redirect()
            ->route('backend.organizations.verticals.index', $organization)
            ->with('success', 'Đã kích hoạt dịch vụ "' . (VerticalRegistry::resolveForOrganization($organization->id, $code)?->label() ?? $code) . '".');
    }

    // ── Deactivate ────────────────────────────────────────────────────────────

    public function deactivate(Request $request, Organization $organization, string $code): RedirectResponse
    {
        $this->authorize('update', $organization);

        (new DeactivateVerticalAction)->execute($organization->id, $code);

        return redirect()
            ->route('backend.organizations.verticals.index', $organization)
            ->with('success', 'Đã tắt dịch vụ "' . (VerticalRegistry::resolveForOrganization($organization->id, $code)?->label() ?? $code) . '".');
    }

    // ── Preview dashboard — xem nhanh dashboard vertical của 1 tổ chức khác ────
    //
    // `deployment.dashboard` (`Modules\Deployment`) dùng middleware `tenant` +
    // `vertical`, tự resolve theo `TenantContext::getOrganizationId()` — tức
    // theo tổ chức của CHÍNH người dùng đang đăng nhập, không phải theo
    // `$organization` trên URL admin đang quản lý. System Admin/super-admin
    // quản lý vertical cho tổ chức khác thường không tự thuộc tổ chức đó
    // (mặc định rơi vào tổ chức "system") → click thẳng link cũ sẽ luôn 403
    // "chưa được kích hoạt cho tổ chức này". Route này ghim tạm tenant context
    // của session sang đúng tổ chức đang xem trước khi redirect sang dashboard
    // thật — theo đúng cơ chế `resolveFromSession` đã có sẵn của
    // `IdentifyOrganization` (không phải cơ chế mới).

    public function previewDashboard(Organization $organization, string $code): RedirectResponse
    {
        $this->authorize('update', $organization);

        $vertical = VerticalRegistry::resolveForOrganization($organization->id, $code);
        if (! $vertical || $vertical->template()->status !== 'active') {
            abort(404, "Vertical '{$code}' chưa được kích hoạt cho tổ chức này.");
        }

        session(['organization_id' => $organization->id]);

        return redirect()->route('deployment.dashboard', ['vertical' => $code]);
    }

    // ── Config — cấu hình chi tiết cho 1 vertical đã active ──────────────────

    public function config(Organization $organization, string $code): View
    {
        $this->authorize('update', $organization);

        $vertical = VerticalRegistry::resolveForOrganization($organization->id, $code);
        if (! $vertical) {
            abort(404, "Vertical '{$code}' không tồn tại.");
        }

        $orgVertical = $vertical->template();
        $phasesData  = $orgVertical->toBuilderPhasesData();

        $configItems = VerticalConfigItem::where('vertical_template_id', $orgVertical->id)
            ->orderBy('config_group')
            ->orderBy('sort_order')
            ->get()
            ->groupBy('config_group');

        return view('organization::verticals.config', compact(
            'organization', 'vertical', 'orgVertical', 'configItems', 'phasesData'
        ));
    }

    // ── Update config ─────────────────────────────────────────────────────────

    public function updateConfig(Request $request, Organization $organization, string $code): RedirectResponse
    {
        $this->authorize('update', $organization);

        $vertical = VerticalRegistry::resolveForOrganization($organization->id, $code);
        if (! $vertical) {
            abort(404, "Vertical '{$code}' không tồn tại.");
        }

        $items = $request->input('items', []);

        foreach ($items as $id => $data) {
            VerticalConfigItem::where('id', (int) $id)
                ->where('vertical_template_id', $vertical->template()->id)
                ->update([
                    'label'     => trim($data['label'] ?? ''),
                    'is_active' => ! empty($data['is_active']),
                ]);
        }

        VerticalRegistry::clearCache($organization->id, $code);

        return redirect()
            ->route('backend.organizations.verticals.config', [$organization, $code])
            ->with('success', 'Đã lưu cấu hình.');
    }

    // ── Thêm/xoá mục danh mục — mỗi tổ chức tự định nghĩa khái niệm riêng ─────
    // (vd. loại issue của tổ chức A khác tổ chức B), không giới hạn ở các mục
    // đã seed sẵn từ thư viện.

    public function storeConfigItem(Request $request, Organization $organization, string $code): RedirectResponse
    {
        $this->authorize('update', $organization);

        $vertical = VerticalRegistry::resolveForOrganization($organization->id, $code);
        if (! $vertical) {
            abort(404, "Vertical '{$code}' không tồn tại.");
        }

        $validated = $request->validate([
            'config_group' => ['required', 'string', Rule::in(['hierarchy', 'activity_type', 'doc_type', 'issue_type'])],
            'item_code'    => ['required', 'string', 'max:50', 'regex:/^[a-z0-9]+(_[a-z0-9]+)*$/'],
            'label'        => ['required', 'string', 'max:100'],
        ], [
            'item_code.required' => 'Vui lòng nhập mã.',
            'item_code.regex'    => 'Mã chỉ gồm chữ thường, số, dấu gạch dưới (vd: sau_benh).',
            'label.required'     => 'Vui lòng nhập nhãn hiển thị.',
        ]);

        $templateId = $vertical->template()->id;

        $exists = VerticalConfigItem::where('vertical_template_id', $templateId)
            ->where('config_group', $validated['config_group'])
            ->where('code', $validated['item_code'])
            ->exists();

        if ($exists) {
            return back()->withErrors(['item_code' => 'Mã này đã tồn tại trong nhóm.'])->withInput();
        }

        $maxSort = VerticalConfigItem::where('vertical_template_id', $templateId)
            ->where('config_group', $validated['config_group'])
            ->max('sort_order');

        VerticalConfigItem::create([
            'vertical_template_id' => $templateId,
            'config_group'         => $validated['config_group'],
            'code'                 => $validated['item_code'],
            'label'                => $validated['label'],
            'is_required'          => false,
            'is_active'            => true,
            'sort_order'           => ($maxSort ?? 0) + 1,
        ]);

        VerticalRegistry::clearCache($organization->id, $code);

        return redirect()
            ->route('backend.organizations.verticals.config', [$organization, $code])
            ->with('success', 'Đã thêm mục cấu hình.');
    }

    public function destroyConfigItem(Organization $organization, string $code, VerticalConfigItem $item): RedirectResponse
    {
        $this->authorize('update', $organization);

        $vertical = VerticalRegistry::resolveForOrganization($organization->id, $code);
        if (! $vertical || $item->vertical_template_id !== $vertical->template()->id) {
            abort(404);
        }

        if ($item->is_required) {
            return back()->withErrors(['item' => 'Không thể xoá mục bắt buộc.']);
        }

        $item->delete();

        VerticalRegistry::clearCache($organization->id, $code);

        return back()->with('success', 'Đã xoá mục cấu hình.');
    }
}
