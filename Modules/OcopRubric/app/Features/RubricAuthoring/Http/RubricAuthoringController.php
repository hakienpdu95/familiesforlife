<?php

namespace Modules\OcopRubric\Features\RubricAuthoring\Http;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\OcopRubric\Enums\RubricVersionStatus;
use Modules\OcopRubric\Features\RubricAuthoring\Actions\PublishRubricVersionAction;
use Modules\OcopRubric\Features\RubricAuthoring\Actions\ReorderCriteriaAction;
use Modules\OcopRubric\Features\RubricAuthoring\Actions\UpsertCriterionAction;
use Modules\OcopRubric\Features\RubricAuthoring\Actions\UpsertOptionAction;
use Modules\OcopRubric\Features\RubricAuthoring\Data\CriterionData;
use Modules\OcopRubric\Features\RubricAuthoring\Data\OptionData;
use Modules\OcopRubric\Features\RubricAuthoring\Queries\GetRubricTreeHandler;
use Modules\OcopRubric\Features\RubricAuthoring\Queries\GetRubricTreeQuery;
use Modules\OcopRubric\Features\RubricAuthoring\Queries\ValidateRubricIntegrityHandler;
use Modules\OcopRubric\Features\RubricAuthoring\Queries\ValidateRubricIntegrityQuery;
use Modules\OcopRubric\Models\OcopProductGroup;
use Modules\OcopRubric\Models\OcopRubricCriterion;
use Modules\OcopRubric\Models\OcopRubricOption;
use Modules\OcopRubric\Models\OcopRubricVersion;

class RubricAuthoringController extends Controller
{
    // ── Versions ─────────────────────────────────────────────────────────
    // Không có UI tạo/nhân bản version — dữ liệu cây tiêu chí đến từ
    // OcopRubricVersionSeeder (đọc fixture, xem docs/ocop-rubric-spec.md §10),
    // không phải nhập tay qua web. Mỗi bộ sản phẩm luôn có tối đa 1 version
    // active tại 1 thời điểm nên xem cây đi thẳng vào version đó, không cần
    // trang danh sách trung gian.

    public function tree(OcopProductGroup $productGroup, OcopRubricVersion $version, GetRubricTreeHandler $handler): View
    {
        $version = $handler->handle(new GetRubricTreeQuery($version->id));

        return view('ocoprubric::admin.rubric-authoring.tree', compact('productGroup', 'version'));
    }

    public function validateIntegrity(OcopRubricVersion $version, ValidateRubricIntegrityHandler $handler): JsonResponse
    {
        $result = $handler->handle(new ValidateRubricIntegrityQuery($version->id));

        return response()->json($result);
    }

    public function publish(OcopProductGroup $productGroup, OcopRubricVersion $version, PublishRubricVersionAction $action): RedirectResponse
    {
        try {
            $action->handle($version, auth()->id());
        } catch (\DomainException $e) {
            return back()->withErrors(['version' => $e->getMessage()]);
        }

        return redirect()
            ->route('ocop_rubric.admin.product-groups.versions.tree', [$productGroup, $version])
            ->with('success', "Đã publish version {$version->version_no} — các version active cũ (nếu có) đã chuyển sang retired.");
    }

    // ── Criteria ─────────────────────────────────────────────────────────

    public function storeCriterion(Request $request): RedirectResponse
    {
        $data = CriterionData::from($this->validatedCriterion($request));
        $this->guardParentDraft($data->parent_id, $data->rubric_section_id);

        app(UpsertCriterionAction::class)->handle($data);

        return back()->with('success', 'Đã thêm tiêu chí.');
    }

    public function updateCriterion(Request $request, OcopRubricCriterion $criterion): RedirectResponse
    {
        $this->guardVersionDraft($criterion->section->rubric_version_id);

        $data = CriterionData::from($this->validatedCriterion($request));
        app(UpsertCriterionAction::class)->handle($data, $criterion);

        return back()->with('success', 'Đã cập nhật tiêu chí.');
    }

    public function destroyCriterion(OcopRubricCriterion $criterion): RedirectResponse
    {
        $this->guardVersionDraft($criterion->section->rubric_version_id);

        $criterion->delete(); // cascadeOnDelete xử lý children/options

        return back()->with('success', 'Đã xóa tiêu chí.');
    }

    /**
     * Đổi thứ tự với anh/em liền kề (form POST đơn giản, reload trang) — thay vì
     * kéo-thả AJAX, vì UI này không thể kiểm thử trực quan trên trình duyệt
     * trong phiên làm việc hiện tại; ưu tiên phương án chắc chắn hơn.
     */
    public function moveCriterion(OcopRubricCriterion $criterion, string $direction): RedirectResponse
    {
        abort_unless(in_array($direction, ['up', 'down'], true), 404);
        $this->guardVersionDraft($criterion->section->rubric_version_id);

        $siblings = OcopRubricCriterion::where('rubric_section_id', $criterion->rubric_section_id)
            ->where('parent_id', $criterion->parent_id)
            ->orderBy('sort_order')
            ->get();

        $index = $siblings->search(fn ($c) => $c->id === $criterion->id);
        $swapIndex = $direction === 'up' ? $index - 1 : $index + 1;

        if ($index !== false && $swapIndex >= 0 && $swapIndex < $siblings->count()) {
            $other = $siblings[$swapIndex];
            [$a, $b] = [$criterion->sort_order, $other->sort_order];
            $criterion->update(['sort_order' => $b]);
            $other->update(['sort_order' => $a]);
        }

        return back()->with('success', 'Đã đổi thứ tự.');
    }

    public function reorderCriteria(Request $request, ReorderCriteriaAction $action): JsonResponse
    {
        $ids = $request->array('ids');
        $action->handle($ids);

        return response()->json(['success' => true]);
    }

    // ── Options ──────────────────────────────────────────────────────────

    public function storeOption(Request $request): RedirectResponse
    {
        $data = OptionData::from($request->validate([
            'criterion_id' => 'required|integer|exists:ocop_rubric_criteria,id',
            'label'        => 'required|string|max:1000',
            'points'       => 'required|numeric|min:0',
            'sort_order'   => 'nullable|integer|min:0',
        ]));

        $criterion = OcopRubricCriterion::with('section')->findOrFail($data->criterion_id);
        $this->guardVersionDraft($criterion->section->rubric_version_id);

        app(UpsertOptionAction::class)->handle($data);

        return back()->with('success', 'Đã thêm phương án.');
    }

    public function updateOption(Request $request, OcopRubricOption $option): RedirectResponse
    {
        $this->guardVersionDraft($option->criterion->section->rubric_version_id);

        $data = OptionData::from($request->validate([
            'criterion_id' => 'required|integer|exists:ocop_rubric_criteria,id',
            'label'        => 'required|string|max:1000',
            'points'       => 'required|numeric|min:0',
            'sort_order'   => 'nullable|integer|min:0',
        ]));

        app(UpsertOptionAction::class)->handle($data, $option);

        return back()->with('success', 'Đã cập nhật phương án.');
    }

    public function destroyOption(OcopRubricOption $option): RedirectResponse
    {
        $this->guardVersionDraft($option->criterion->section->rubric_version_id);

        $option->delete();

        return back()->with('success', 'Đã xóa phương án.');
    }

    // ── Helpers ──────────────────────────────────────────────────────────

    private function validatedCriterion(Request $request): array
    {
        return $request->validate([
            'rubric_section_id' => 'required|integer|exists:ocop_rubric_sections,id',
            'parent_id'         => 'nullable|integer|exists:ocop_rubric_criteria,id',
            'code'              => 'required|string|max:20',
            'label'             => 'required|string|max:500',
            'max_score'         => 'required|numeric|min:0',
            'requirement_note'  => 'nullable|string',
            'is_scorable'       => 'boolean',
            'sort_order'        => 'nullable|integer|min:0',
        ]);
    }

    /** Chặn sửa cây khi version không còn ở trạng thái draft — active/retired bất biến (spec §4). */
    private function guardVersionDraft(int $rubricVersionId): void
    {
        $status = OcopRubricVersion::whereKey($rubricVersionId)->value('status');
        if ($status !== RubricVersionStatus::Draft->value) {
            abort(422, 'Chỉ có thể sửa cây tiêu chí khi version đang ở trạng thái draft.');
        }
    }

    private function guardParentDraft(?int $parentId, int $rubricSectionId): void
    {
        $versionId = \Modules\OcopRubric\Models\OcopRubricSection::whereKey($rubricSectionId)->value('rubric_version_id');
        $this->guardVersionDraft($versionId);
    }
}
