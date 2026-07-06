<?php

namespace Modules\Deployment\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Deployment\Actions\AddChecklistNoteAction;
use Modules\Deployment\Actions\AssignChecklistItemAction;
use Modules\Deployment\Actions\ToggleChecklistItemAction;
use Modules\Deployment\Data\AddChecklistNoteData;
use Modules\Deployment\Data\AssignChecklistItemData;
use Modules\Deployment\Models\DeploymentChecklistItem;
use Modules\Deployment\Models\DeploymentTarget;
use Modules\Employee\Models\Employee;

class DeploymentChecklistController extends Controller
{
    public function mobile(Request $request, DeploymentTarget $target): View
    {
        $this->authorize('view', $target);

        $vertical = $request->attributes->get('_vertical');
        $target->load([
            'targetOrganization',
            'checklistItems' => fn($q) => $q->orderBy('phase')->orderBy('id')
                ->with(['progressLogs.loggedBy', 'assignedEmployee']),
        ]);
        $checklist   = $target->checklistItems->groupBy('phase');
        $phaseLabels = $vertical->phaseLabels();
        // Scope theo target_organization_id (tổ chức đang được triển khai), không phải
        // organization_id (tenant vận hành) — xem ghi chú ở DeploymentTargetController::show().
        $employees   = Employee::withoutTenant()
            ->where('organization_id', $target->target_organization_id)
            ->orderBy('full_name')
            ->get(['id', 'full_name', 'employee_code']);

        return view('deployment::mobile.checklist', compact('vertical', 'target', 'checklist', 'phaseLabels', 'employees'));
    }

    public function toggle(
        Request $request,
        DeploymentChecklistItem $item,
        ToggleChecklistItemAction $action
    ): RedirectResponse {
        $target   = DeploymentTarget::findOrFail($item->deployment_target_id);
        $vertical = $request->attributes->get('_vertical');

        $this->authorize('toggleChecklist', $target);

        $action->handle($item);

        return back()->with('success', $item->is_done
            ? "Đã đánh dấu hoàn thành: {$item->item_label}"
            : "Đã bỏ đánh dấu: {$item->item_label}"
        );
    }

    public function addNote(
        Request $request,
        DeploymentChecklistItem $item,
        AddChecklistNoteAction $action
    ): RedirectResponse {
        $target = DeploymentTarget::findOrFail($item->deployment_target_id);

        $this->authorize('toggleChecklist', $target);

        $data = AddChecklistNoteData::validateAndCreate($request->all());
        $action->handle($item, $data);

        return back()->with('success', "Đã thêm ghi chú cho: {$item->item_label}");
    }

    public function assignEmployee(
        Request $request,
        DeploymentChecklistItem $item,
        AssignChecklistItemAction $action
    ): RedirectResponse {
        $target = DeploymentTarget::findOrFail($item->deployment_target_id);

        $this->authorize('update', $target);

        $data = AssignChecklistItemData::validateAndCreate($request->all());
        $action->handle($item, $data);

        return back()->with('success', "Đã cập nhật người phụ trách: {$item->item_label}");
    }
}
