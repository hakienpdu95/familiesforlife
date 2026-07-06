<?php

namespace Modules\BusinessBlueprint\Features\BlueprintAuthoring\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\BusinessBlueprint\Features\BlueprintAuthoring\Actions\CreateBlueprintAction;
use Modules\BusinessBlueprint\Features\BlueprintAuthoring\Data\BlueprintData;
use Modules\BusinessBlueprint\Models\Blueprint;
use Modules\BusinessSolution\Models\BusinessSolution;

class BlueprintController extends Controller
{
    public function index(Request $request): View
    {
        $blueprints = Blueprint::query()
            ->with('businessSolution', 'currentVersion')
            ->when($request->filled('business_solution_id'), fn ($q) => $q->where('business_solution_id', $request->integer('business_solution_id')))
            ->orderBy('name')
            ->get();

        $businessSolutions = BusinessSolution::query()->orderBy('name')->get();

        return view('businessblueprint::admin.blueprints.index', compact('blueprints', 'businessSolutions'));
    }

    public function create(): View
    {
        $businessSolutions = BusinessSolution::query()->orderBy('name')->get();

        return view('businessblueprint::admin.blueprints.create', compact('businessSolutions'));
    }

    public function store(Request $request, CreateBlueprintAction $action): RedirectResponse
    {
        $data      = BlueprintData::from($this->validated($request));
        $blueprint = $action->handle($data, auth()->id());

        return redirect()
            ->route('business_blueprint.admin.versions.tree', [$blueprint, $blueprint->current_version_id])
            ->with('success', "Blueprint \"{$blueprint->name}\" đã được tạo (version 1.0.0, draft).");
    }

    public function edit(Blueprint $blueprint): View
    {
        $businessSolutions = BusinessSolution::query()->orderBy('name')->get();

        return view('businessblueprint::admin.blueprints.edit', compact('blueprint', 'businessSolutions'));
    }

    public function update(Request $request, Blueprint $blueprint): RedirectResponse
    {
        $data = BlueprintData::from($this->validated($request, $blueprint->id));
        $blueprint->update([
            'business_solution_id' => $data->business_solution_id,
            'code'                  => $data->code,
            'name'                  => $data->name,
            'description'           => $data->description,
        ]);

        return redirect()->route('business_blueprint.admin.index')
            ->with('success', 'Cập nhật Blueprint thành công.');
    }

    public function destroy(Blueprint $blueprint): RedirectResponse
    {
        if ($blueprint->versions()->where('status', '!=', 'draft')->exists()) {
            return back()->withErrors(['blueprint' => 'Không thể xóa Blueprint đã có version publish/archive.']);
        }

        $blueprint->delete();

        return redirect()->route('business_blueprint.admin.index')
            ->with('success', "Đã xóa Blueprint \"{$blueprint->name}\".");
    }

    private function validated(Request $request, ?int $ignoreId = null): array
    {
        $codeRule = 'required|string|max:50|regex:/^[A-Z0-9\-]+$/|unique:blueprints,code'
            . ($ignoreId ? ",{$ignoreId}" : '');

        // Input hiển thị chữ hoa qua CSS (class="uppercase") nhưng giá trị submit vẫn
        // giữ nguyên chữ người dùng gõ — chuẩn hoá về chữ hoa thật trước khi validate.
        if ($request->filled('code')) {
            $request->merge(['code' => strtoupper((string) $request->string('code'))]);
        }

        return $request->validate([
            'business_solution_id' => 'required|integer|exists:business_solutions,id',
            'code'                  => $codeRule,
            'name'                  => 'required|string|max:255',
            'description'           => 'nullable|string',
        ]);
    }
}
