<?php

namespace Modules\AIVideoStudioTemplate\Features\ProjectManagement\Http;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Modules\AIVideoStudioTemplate\Features\ProjectManagement\Actions\CompileProjectDirectorPromptAction;
use Modules\AIVideoStudioTemplate\Features\ProjectManagement\Actions\CreateProjectAction;
use Modules\AIVideoStudioTemplate\Features\ProjectManagement\Actions\DeleteProjectAction;
use Modules\AIVideoStudioTemplate\Features\ProjectManagement\Actions\UpdateProjectAction;
use Modules\AIVideoStudioTemplate\Features\ProjectManagement\Data\ProjectInputData;
use Modules\AIVideoStudioTemplate\Features\ProjectManagement\Http\Requests\StoreProjectRequest;
use Modules\AIVideoStudioTemplate\Features\ProjectManagement\Http\Requests\UpdateProjectRequest;
use Modules\AIVideoStudioTemplate\Models\AiVideoStudioProject;

/**
 * spec/AIVideoStudioTemplate_Technical_Specification.md §5/§6 — gate phẳng bằng middleware
 * 'can:ai_video_studio_template.use' ở routes/web.php, KHÔNG Policy riêng theo model (mọi người
 * có permission xem/sửa/xoá được MỌI project).
 */
class ProjectController extends Controller
{
    /** Dữ liệu bảng lấy qua ProjectApiController (Tabulator, remote pagination/sort/filter). */
    public function index(): View
    {
        return view('aivideostudiotemplate::index');
    }

    public function create(): View
    {
        return view('aivideostudiotemplate::create');
    }

    public function store(StoreProjectRequest $request, CreateProjectAction $action): RedirectResponse
    {
        $data = ProjectInputData::from($request->toData());
        $project = $action->handle($data, $request->user()->id);

        return redirect()->route('backend.aivideostudiotemplate.show', $project)
            ->with('success', 'Đã tạo project — thêm shot bên dưới để bắt đầu soạn Director Prompt Template.');
    }

    public function show(AiVideoStudioProject $project, CompileProjectDirectorPromptAction $compilePrompt): View
    {
        $project->load(['shots.createdBy', 'createdBy']);

        return view('aivideostudiotemplate::show', [
            'project' => $project,
            'compiledDocument' => $compilePrompt->handle($project),
        ]);
    }

    public function edit(AiVideoStudioProject $project): View
    {
        return view('aivideostudiotemplate::edit', [
            'project' => $project,
        ]);
    }

    public function update(UpdateProjectRequest $request, AiVideoStudioProject $project, UpdateProjectAction $action): RedirectResponse
    {
        $data = ProjectInputData::from($request->toData());
        $action->handle($project, $data, $request->user()->id);

        return redirect()->route('backend.aivideostudiotemplate.show', $project)
            ->with('success', 'Đã cập nhật project.');
    }

    public function destroy(AiVideoStudioProject $project, DeleteProjectAction $action): RedirectResponse
    {
        $name = $project->name;
        $action->handle($project);

        return redirect()->route('backend.aivideostudiotemplate.index')
            ->with('success', "Đã xoá project \"{$name}\" và toàn bộ shot bên trong.");
    }

    /** spec §6/§8 — tải file .md ghép các compiled_prompt theo sort_order (CompileProjectDirectorPromptAction). */
    public function export(AiVideoStudioProject $project, CompileProjectDirectorPromptAction $action): Response
    {
        $content = $action->handle($project);
        $filename = Str::slug($project->name).'-director-prompt-template.md';

        return response($content, 200, [
            'Content-Type' => 'text/markdown; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }
}
