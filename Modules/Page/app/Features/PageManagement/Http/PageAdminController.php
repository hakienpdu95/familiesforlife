<?php

namespace Modules\Page\Features\PageManagement\Http;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Modules\Page\Features\PageManagement\Actions\CreatePageAction;
use Modules\Page\Features\PageManagement\Actions\DeletePageAction;
use Modules\Page\Features\PageManagement\Actions\PublishPageAction;
use Modules\Page\Features\PageManagement\Actions\UnpublishPageAction;
use Modules\Page\Features\PageManagement\Actions\UpdatePageAction;
use Modules\Page\Features\PageManagement\Data\PageData;
use Modules\Page\Features\PageManagement\PageTemplate;
use Modules\Page\Models\Page;

class PageAdminController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Page::class, 'page');
    }

    /** Dữ liệu bảng lấy qua PageApiController (Tabulator, remote pagination/sort/filter). */
    public function index(): View
    {
        return view('page::admin.pages.index');
    }

    public function create(): View
    {
        return view('page::admin.pages.create', [
            'templateOptions' => PageTemplate::options(),
        ]);
    }

    public function store(Request $request, CreatePageAction $action): RedirectResponse
    {
        $data = PageData::from($this->validated($request));
        $page = $action->handle($data);

        return redirect()->route('backend.page.items.edit', $page)
            ->with('success', __('Trang ":title" đã được tạo (Nháp).', ['title' => $page->title]));
    }

    public function edit(Page $page): View
    {
        return view('page::admin.pages.edit', [
            'page'            => $page,
            'templateOptions' => PageTemplate::options(),
        ]);
    }

    public function update(Request $request, Page $page, UpdatePageAction $action): RedirectResponse
    {
        $data = PageData::from($this->validated($request, $page));
        $action->handle($page, $data);

        return redirect()->route('backend.page.items.edit', $page)
            ->with('success', __('Cập nhật trang thành công.'));
    }

    public function destroy(Request $request, Page $page, DeletePageAction $action): RedirectResponse|JsonResponse
    {
        $title = $page->title;

        try {
            $action->handle($page);
        } catch (\Illuminate\Validation\ValidationException $e) {
            if ($request->expectsJson()) {
                return response()->json(['message' => collect($e->errors())->flatten()->first() ?? $e->getMessage()], 422);
            }

            throw $e;
        }

        if ($request->expectsJson()) {
            return response()->json(['message' => __('Đã xoá trang ":title".', ['title' => $title])]);
        }

        return redirect()->route('backend.page.items.index')
            ->with('success', __('Đã xoá trang ":title".', ['title' => $title]));
    }

    public function publish(Request $request, Page $page, PublishPageAction $action): RedirectResponse|JsonResponse
    {
        $this->authorize('update', $page);

        try {
            $action->handle($page);
        } catch (\Illuminate\Validation\ValidationException $e) {
            if ($request->expectsJson()) {
                return response()->json(['message' => collect($e->errors())->flatten()->first() ?? $e->getMessage()], 422);
            }

            throw $e;
        }

        if ($request->expectsJson()) {
            return response()->json(['message' => __('Đã xuất bản trang ":title".', ['title' => $page->title])]);
        }

        return back()->with('success', __('Đã xuất bản trang ":title".', ['title' => $page->title]));
    }

    public function unpublish(Request $request, Page $page, UnpublishPageAction $action): RedirectResponse|JsonResponse
    {
        $this->authorize('update', $page);

        $action->handle($page);

        if ($request->expectsJson()) {
            return response()->json(['message' => __('Đã chuyển trang ":title" về Nháp.', ['title' => $page->title])]);
        }

        return back()->with('success', __('Đã chuyển trang ":title" về Nháp.', ['title' => $page->title]));
    }

    /**
     * spec/Page_Static_Pages_Technical_Specification.md §4.1.1 — reserved_slugs là lớp
     * validate độc lập với Route::fallback(); §3.3 — template phải nằm trong PageTemplate::MAP.
     */
    private function validated(Request $request, ?Page $ignoring = null): array
    {
        return $request->validate([
            'title'             => ['required', 'string', 'max:200'],
            'slug'              => [
                'nullable', 'string', 'max:160', 'alpha_dash:ascii',
                Rule::unique('pages', 'slug')->ignore($ignoring?->id),
                Rule::notIn(config('page.reserved_slugs', [])),
            ],
            'template'          => ['required', Rule::in(array_keys(PageTemplate::options()))],
            'excerpt'           => ['nullable', 'string', 'max:500'],
            'content'           => ['nullable', 'string'],
            'seo_title'         => ['nullable', 'string', 'max:200'],
            'seo_description'   => ['nullable', 'string', 'max:300'],
            'seo_noindex'       => ['boolean'],
            'sort_order'        => ['integer', 'min:0'],
        ], [
            'slug.not_in' => __('Đường dẫn này đã được hệ thống sử dụng, vui lòng chọn đường dẫn khác.'),
            'template.in' => __('Thiết kế đã chọn không tồn tại.'),
        ]);
    }
}
