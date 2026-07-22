<?php

namespace Modules\Post\Features\TagManagement\Http;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Post\Features\TagManagement\Actions\CreateTagAction;
use Modules\Post\Features\TagManagement\Actions\DeleteTagAction;
use Modules\Post\Features\TagManagement\Actions\MergeTagsAction;
use Modules\Post\Features\TagManagement\Actions\UpdateTagAction;
use Modules\Post\Features\TagManagement\Data\TagData;
use Modules\Post\Models\PostTag;

class TagAdminController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(PostTag::class, 'tag');
    }

    /**
     * Dữ liệu bảng lấy qua TagApiController (Tabulator, phân trang/sắp xếp/tìm kiếm từ xa) —
     * chỉ truyền $allTags (không phân trang, id+name) cho dropdown "Gộp vào tag" trong modal,
     * vì tag đích phải chọn được từ TOÀN BỘ tag, không riêng trang hiện tại của Tabulator.
     */
    public function index(): View
    {
        $allTags = PostTag::orderBy('name')->get(['id', 'name']);

        return view('post::admin.tags.index', compact('allTags'));
    }

    public function create(): View
    {
        return view('post::admin.tags.create');
    }

    public function store(Request $request, CreateTagAction $action): RedirectResponse
    {
        $data = TagData::from($this->validated($request));
        $tag  = $action->handle($data);

        return redirect()->route('backend.post.tags.index')
            ->with('success', "Tag \"{$tag->name}\" đã được tạo.");
    }

    public function edit(PostTag $tag): View
    {
        return view('post::admin.tags.edit', compact('tag'));
    }

    public function update(Request $request, PostTag $tag, UpdateTagAction $action): RedirectResponse
    {
        $data = TagData::from($this->validated($request));
        $action->handle($tag, $data);

        return redirect()->route('backend.post.tags.index')
            ->with('success', 'Cập nhật tag thành công.');
    }

    public function destroy(Request $request, PostTag $tag, DeleteTagAction $action): RedirectResponse|JsonResponse
    {
        $name = $tag->name;

        try {
            $action->handle($tag);
        } catch (\RuntimeException $e) {
            if ($request->expectsJson()) {
                return response()->json(['message' => $e->getMessage()], 422);
            }

            return back()->withErrors(['tag' => $e->getMessage()]);
        }

        if ($request->expectsJson()) {
            return response()->json(['message' => "Đã xoá tag \"{$name}\"."]);
        }

        return redirect()->route('backend.post.tags.index')
            ->with('success', "Đã xoá tag \"{$name}\".");
    }

    public function merge(Request $request, PostTag $tag, MergeTagsAction $action): RedirectResponse|JsonResponse
    {
        $this->authorize('delete', $tag);

        // Không validate "khác tag nguồn" ở đây bằng rule 'different' — Laravel so khớp theo
        // tên field trong request, không so được với route model {tag}. Việc chặn gộp vào
        // chính nó đã có ở MergeTagsAction::handle() (ném ValidationException, bắt bên dưới).
        $validated = $request->validate([
            'target_tag_id' => ['required', 'integer', 'exists:post_tags,id'],
        ]);

        $targetTag = PostTag::findOrFail($validated['target_tag_id']);
        $sourceName = $tag->name;

        try {
            $action->handle($tag, $targetTag);
        } catch (\Illuminate\Validation\ValidationException $e) {
            if ($request->expectsJson()) {
                return response()->json(['message' => collect($e->errors())->flatten()->first() ?? $e->getMessage()], 422);
            }

            return back()->withErrors($e->errors());
        }

        if ($request->expectsJson()) {
            return response()->json(['message' => "Đã gộp tag \"{$sourceName}\" vào \"{$targetTag->name}\"."]);
        }

        return redirect()->route('backend.post.tags.index')
            ->with('success', "Đã gộp tag \"{$sourceName}\" vào \"{$targetTag->name}\".");
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:120'],
        ]);
    }
}
