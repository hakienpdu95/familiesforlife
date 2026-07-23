<?php

namespace Modules\Post\Features\BreakingNews\Http;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Modules\Post\Features\BreakingNews\Actions\CreateBreakingNewsAction;
use Modules\Post\Features\BreakingNews\Actions\DeleteBreakingNewsAction;
use Modules\Post\Features\BreakingNews\Actions\UpdateBreakingNewsAction;
use Modules\Post\Features\BreakingNews\Data\BreakingNewsData;
use Modules\Post\Models\PostArticleTranslation;
use Modules\Post\Models\PostBreakingNews;

/**
 * spec/Breaking_News_Ticker_Technical_Specification.md §6 — không có bước duyệt (bài viết đã
 * published từ trước, ở đây chỉ "đánh dấu thêm"), tạo xong hiển thị ngay nếu is_active=true và
 * trong khoảng giờ hợp lệ.
 */
class BreakingNewsAdminController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(PostBreakingNews::class, 'breakingNews');
    }

    /** Dữ liệu bảng lấy qua BreakingNewsApiController (Tabulator, remote pagination/sort/filter). */
    public function index(): View
    {
        return view('post::admin.breaking-news.index');
    }

    public function create(): View
    {
        // §10.1 (spec) — "cảnh báo mềm khi admin chọn bài đang có dòng active khác": danh sách
        // article_id đang active truyền sẵn cho JS so khớp lúc chọn, không cần round-trip AJAX
        // riêng (số dòng active thường nhỏ — xem PostBreakingNews::scopeActive()).
        $activeArticleIds = PostBreakingNews::active()->pluck('article_id')->all();

        return view('post::admin.breaking-news.create', compact('activeArticleIds'));
    }

    /**
     * Chọn NHIỀU bài viết cùng lúc — mỗi bài tạo 1 bản ghi PostBreakingNews riêng (article_id
     * là quan hệ 1-1 với 1 dòng, không có "1 dòng nhiều bài"), dùng CHUNG các trường lịch/nhãn
     * đã điền 1 lần trong form (headline_override/badge_label/starts_at/ends_at/is_active) —
     * hợp lý vì các trường này thường để trống/dùng giá trị mặc định khi tạo hàng loạt, muốn
     * tuỳ biến riêng cho từng bài thì sửa lại sau qua trang "Sửa" (vẫn chỉ chọn 1 bài).
     *
     * sort_order tăng dần theo THỨ TỰ đã chọn (không dùng chung 1 giá trị cho cả batch) — nếu
     * không, nhiều dòng cùng sort_order + cùng starts_at (rất dễ xảy ra khi tạo hàng loạt) sẽ
     * hoà nhau ở PostBreakingNews::currentList() (không có tiêu chí phụ nào khác để phá thế
     * hoà), khiến thứ tự xuất hiện trên ticker không xác định.
     */
    public function store(Request $request, CreateBreakingNewsAction $action): RedirectResponse
    {
        $validated  = $this->validatedForStore($request);
        $articleIds = $validated['article_ids'];

        $created = DB::transaction(function () use ($articleIds, $validated, $action) {
            $count = 0;
            foreach (array_values($articleIds) as $index => $articleId) {
                $action->handle(BreakingNewsData::from([
                    'article_id'         => $articleId,
                    // Field nullable KHÔNG gửi trong request → Laravel validate() không trả về
                    // key đó luôn (không phải trả về null) — ?? null/mặc định để tránh
                    // "Undefined array key" khi form để trống các trường tuỳ chọn này.
                    'headline_override'  => $validated['headline_override'] ?? null,
                    'badge_label'        => $validated['badge_label'] ?? null,
                    'starts_at'          => $validated['starts_at'] ?? null,
                    'ends_at'            => $validated['ends_at'] ?? null,
                    'sort_order'         => ($validated['sort_order'] ?? 0) + $index,
                    'is_active'          => $validated['is_active'] ?? true,
                ]));
                $count++;
            }

            return $count;
        });

        $message = $created === 1
            ? 'Đã đánh dấu tin nóng.'
            : "Đã đánh dấu tin nóng cho {$created} bài viết.";

        return redirect()->route('backend.post.breaking-news.items.index')->with('success', $message);
    }

    public function edit(PostBreakingNews $breakingNews): View
    {
        $breakingNews->load(['article.translations' => fn ($t) => $t->where('locale', config('post.default_locale'))]);

        // Loại chính bản ghi đang sửa — chọn lại đúng bài viết hiện tại của nó không phải "trùng
        // lặp", chỉ cảnh báo khi trùng với 1 dòng active KHÁC.
        $activeArticleIds = PostBreakingNews::active()
            ->where('id', '!=', $breakingNews->id)
            ->pluck('article_id')
            ->all();

        return view('post::admin.breaking-news.edit', compact('breakingNews', 'activeArticleIds'));
    }

    public function update(Request $request, PostBreakingNews $breakingNews, UpdateBreakingNewsAction $action): RedirectResponse
    {
        $data = BreakingNewsData::from($this->validated($request));
        $action->handle($breakingNews, $data);

        return redirect()->route('backend.post.breaking-news.items.index')
            ->with('success', 'Cập nhật tin nóng thành công.');
    }

    public function destroy(Request $request, PostBreakingNews $breakingNews, DeleteBreakingNewsAction $action): RedirectResponse|JsonResponse
    {
        $action->handle($breakingNews);

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Đã gỡ tin nóng.']);
        }

        return redirect()->route('backend.post.breaking-news.items.index')
            ->with('success', 'Đã gỡ tin nóng.');
    }

    /** Sửa — vẫn đúng 1 bài/1 bản ghi, giữ nguyên `article_id` số đơn (khác store(), xem validatedForStore()). */
    private function validated(Request $request): array
    {
        return $request->validate([
            // §5.1 — chỉ được chọn bài có ít nhất 1 bản dịch published ĐÚNG locale công khai
            // (config('post.default_locale')), cùng điều kiện PostBreakingNews::currentList().
            'article_id' => ['required', 'integer', 'exists:post_articles,id', $this->publishedTranslationRule()],
            'headline_override' => ['nullable', 'string', 'max:300'],
            'badge_label'       => ['nullable', 'string', 'max:40'],
            'starts_at'         => ['nullable', 'date'],
            'ends_at'           => ['nullable', 'date', 'after_or_equal:starts_at'],
            'sort_order'        => ['integer', 'min:0'],
            'is_active'         => ['boolean'],
        ]);
    }

    /** Tạo — chọn được NHIỀU bài (`article_ids[]`), xem docblock store(). */
    private function validatedForStore(Request $request): array
    {
        return $request->validate([
            'article_ids'   => ['required', 'array', 'min:1'],
            'article_ids.*' => ['integer', 'distinct', 'exists:post_articles,id', $this->publishedTranslationRule()],
            'headline_override' => ['nullable', 'string', 'max:300'],
            'badge_label'       => ['nullable', 'string', 'max:40'],
            'starts_at'         => ['nullable', 'date'],
            'ends_at'           => ['nullable', 'date', 'after_or_equal:starts_at'],
            'sort_order'        => ['integer', 'min:0'],
            'is_active'         => ['boolean'],
        ]);
    }

    private function publishedTranslationRule(): \Closure
    {
        return function ($attribute, $value, $fail) {
            $hasPublishedTranslation = PostArticleTranslation::where('article_id', $value)
                ->where('locale', config('post.default_locale'))
                ->published()
                ->exists();

            if (! $hasPublishedTranslation) {
                $fail('Bài viết phải có bản dịch đã xuất bản (đúng ngôn ngữ công khai) mới đánh dấu được là tin nóng.');
            }
        };
    }
}
