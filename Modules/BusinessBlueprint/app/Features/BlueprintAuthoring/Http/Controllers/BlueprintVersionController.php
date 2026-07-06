<?php

namespace Modules\BusinessBlueprint\Features\BlueprintAuthoring\Http\Controllers;

use App\Http\Controllers\Controller;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\BusinessBlueprint\Features\BlueprintAuthoring\Actions\CloneBlueprintVersionAction;
use Modules\BusinessBlueprint\Features\BlueprintAuthoring\Actions\CompareBlueprintVersionsAction;
use Modules\BusinessBlueprint\Features\BlueprintAuthoring\Actions\ArchiveBlueprintVersionAction;
use Modules\BusinessBlueprint\Features\BlueprintAuthoring\Actions\PublishBlueprintVersionAction;
use Modules\BusinessBlueprint\Features\BlueprintAuthoring\Queries\ListBlueprintVersionsHandler;
use Modules\BusinessBlueprint\Features\BlueprintAuthoring\Queries\ListBlueprintVersionsQuery;
use Modules\BusinessBlueprint\Models\Blueprint;
use Modules\BusinessBlueprint\Models\BlueprintVersion;

class BlueprintVersionController extends Controller
{
    public function index(Blueprint $blueprint, ListBlueprintVersionsHandler $handler): View
    {
        $versions = $handler->handle(new ListBlueprintVersionsQuery($blueprint->id));

        return view('businessblueprint::admin.blueprints.versions', compact('blueprint', 'versions'));
    }

    public function clone(Request $request, Blueprint $blueprint, BlueprintVersion $version, CloneBlueprintVersionAction $action): RedirectResponse
    {
        $level = $request->string('level')->value() ?: 'minor';
        $newVersion = $action->handle($version, $level);

        return redirect()
            ->route('business_blueprint.admin.versions.tree', [$blueprint, $newVersion])
            ->with('success', "Đã nhân bản sang version {$newVersion->version} (draft).");
    }

    public function publish(Blueprint $blueprint, BlueprintVersion $version, PublishBlueprintVersionAction $action): RedirectResponse
    {
        try {
            $action->handle($version, auth()->id());
        } catch (DomainException $e) {
            return back()->withErrors(['version' => $e->getMessage()]);
        }

        return redirect()
            ->route('business_blueprint.admin.versions.tree', [$blueprint, $version])
            ->with('success', "Đã phát hành version {$version->version} — version published cũ (nếu có) đã chuyển sang deprecated.");
    }

    public function archive(Blueprint $blueprint, BlueprintVersion $version, ArchiveBlueprintVersionAction $action): RedirectResponse
    {
        $action->handle($version);

        return redirect()
            ->route('business_blueprint.admin.versions.index', $blueprint)
            ->with('success', "Đã lưu trữ version {$version->version}.");
    }

    public function compare(Request $request, Blueprint $blueprint, CompareBlueprintVersionsAction $action): JsonResponse
    {
        $from = BlueprintVersion::where('blueprint_id', $blueprint->id)->findOrFail($request->integer('from'));
        $to   = BlueprintVersion::where('blueprint_id', $blueprint->id)->findOrFail($request->integer('to'));

        return response()->json($action->handle($from, $to)->toArray());
    }
}
