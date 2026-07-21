<?php

namespace Modules\Post\Features\TagManagement\Http;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Post\Features\TagManagement\Actions\CreateTagAction;
use Modules\Post\Features\TagManagement\Actions\DeleteTagAction;
use Modules\Post\Features\TagManagement\Actions\MergeTagsAction;
use Modules\Post\Features\TagManagement\Actions\UpdateTagAction;
use Modules\Post\Features\TagManagement\Data\TagData;
use Modules\Post\Features\TagManagement\Queries\ListTagsForAdminHandler;
use Modules\Post\Features\TagManagement\Queries\ListTagsForAdminQuery;
use Modules\Post\Models\PostTag;

class TagAdminController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(PostTag::class, 'tag');
    }

    public function index(Request $request, ListTagsForAdminHandler $handler): View
    {
        $search = $request->string('q')->value() ?: null;
        $tags   = $handler->handle(new ListTagsForAdminQuery(search: $search));

        return view('post::admin.tags.index', compact('tags', 'search'));
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

    public function destroy(PostTag $tag, DeleteTagAction $action): RedirectResponse
    {
        try {
            $action->handle($tag);
        } catch (\RuntimeException $e) {
            return back()->withErrors(['tag' => $e->getMessage()]);
        }

        return redirect()->route('backend.post.tags.index')
            ->with('success', "Đã xoá tag \"{$tag->name}\".");
    }

    public function merge(Request $request, PostTag $tag, MergeTagsAction $action): RedirectResponse
    {
        $this->authorize('delete', $tag);

        // Không validate "khác tag nguồn" ở đây bằng rule 'different' — Laravel so khớp theo
        // tên field trong request, không so được với route model {tag}. Việc chặn gộp vào
        // chính nó đã có ở MergeTagsAction::handle() (ném ValidationException, bắt bên dưới).
        $validated = $request->validate([
            'target_tag_id' => ['required', 'integer', 'exists:post_tags,id'],
        ]);

        $targetTag = PostTag::findOrFail($validated['target_tag_id']);

        try {
            $action->handle($tag, $targetTag);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()->withErrors($e->errors());
        }

        return redirect()->route('backend.post.tags.index')
            ->with('success', "Đã gộp tag \"{$tag->name}\" vào \"{$targetTag->name}\".");
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:120'],
        ]);
    }
}
