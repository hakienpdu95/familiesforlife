<?php

namespace Modules\Post\Features\ArticleAuthoring\Http;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Modules\Post\Enums\ArticleFormat;
use Modules\Post\Enums\SponsorLabel;
use Modules\Post\Features\ArticleAuthoring\Actions\CreateArticleAction;
use Modules\Post\Features\ArticleAuthoring\Actions\CreateTranslationAction;
use Modules\Post\Features\ArticleAuthoring\Actions\DeleteArticleAction;
use Modules\Post\Features\ArticleAuthoring\Actions\PublishAllTranslationsAction;
use Modules\Post\Features\ArticleAuthoring\Actions\RemoveSponsorshipAction;
use Modules\Post\Features\ArticleAuthoring\Actions\UpdateArticleAction;
use Modules\Post\Features\ArticleAuthoring\Data\ArticleData;
use Modules\Post\Features\ArticleAuthoring\Data\TranslationData;
use Modules\Post\Features\ArticleAuthoring\Queries\ListArticlesForAdminHandler;
use Modules\Post\Features\ArticleAuthoring\Queries\ListArticlesForAdminQuery;
use Modules\Post\Features\ArticleAuthoring\Queries\ListPendingReviewTranslationsHandler;
use Modules\Post\Features\ArticleAuthoring\Queries\ListPendingReviewTranslationsQuery;
use Modules\Post\Features\CategoryManagement\Queries\GetCategoryTreeHandler;
use Modules\Post\Features\CategoryManagement\Queries\GetCategoryTreeQuery;
use Modules\Post\Features\CategoryManagement\Queries\ListCategoriesForAdminHandler;
use Modules\Post\Features\CategoryManagement\Queries\ListCategoriesForAdminQuery;
use Modules\Post\Models\PostArticle;
use Modules\Post\Support\ArticleContentRenderer;
use Modules\Ocop\Models\OcopProduct;

/**
 * PostArticle (§9) chỉ còn là "vỏ" dùng chung mọi ngôn ngữ (format/cover/categories/tags) —
 * KHÔNG còn title/status nên KHÔNG dùng authorizeResource(PostArticle::class) (Policy giờ
 * thao tác PostArticleTranslation, xem PostArticlePolicy). viewAny/create không nhận model
 * nên vẫn map thẳng qua Policy; show/edit/update/destroy check quyền + ownership trực tiếp.
 */
class ArticleAdminController extends Controller
{
    public function index(Request $request, ListArticlesForAdminHandler $handler, ListCategoriesForAdminHandler $categoryHandler): View
    {
        $this->authorize('viewAny', PostArticle::class);

        $articles = $handler->handle(new ListArticlesForAdminQuery(
            page:       max(1, $request->integer('page', 1)),
            search:     $request->string('q')->value() ?: null,
            categoryId: $request->integer('category_id') ?: null,
            format:     $request->string('format')->value() ?: null,
            status:     $request->string('status')->value() ?: null,
        ));

        $categories = $categoryHandler->handle(new ListCategoriesForAdminQuery());

        return view('post::admin.articles.index', compact('articles', 'categories'));
    }

    /**
     * Hàng chờ duyệt xuyên tổ chức cho content_editor/content_head (Platform Approval Gateway
     * — spec/Workflow_Approval_Technical_Specification.md §18.10). Chỉ 2 role này truy cập —
     * user thường dùng index() ở trên (đã lọc theo tổ chức của họ).
     */
    public function pendingReview(Request $request, ListPendingReviewTranslationsHandler $handler): View
    {
        abort_unless($request->user()->isPlatformContentEditor() || $request->user()->isPlatformContentHead(), 403);

        $translations = $handler->handle(new ListPendingReviewTranslationsQuery($request->user()));

        return view('post::admin.articles.pending-review', compact('translations'));
    }

    public function create(GetCategoryTreeHandler $categoryTreeHandler): View
    {
        $this->authorize('create', PostArticle::class);

        // Cây danh mục thật (cha/con/cháu...) thay vì danh sách phẳng — picker "Danh mục" cần
        // hiển thị đúng cấp bậc phân cấp thay vì chỉ 1 dòng "cha › con".
        $categoryTree = $categoryTreeHandler->handle(new GetCategoryTreeQuery());

        return view('post::admin.articles.create', compact('categoryTree'));
    }

    /**
     * Gộp tạo "vỏ" PostArticle + bản dịch đầu tiên (ngôn ngữ chính) trong CÙNG 1 lượt submit —
     * khôi phục trải nghiệm "1 bước bấm Thêm mới là vào thẳng bài viết sẵn sàng soạn nội dung"
     * trước khi PostArticle tách vỏ đa ngôn ngữ (Publishing Engine), không tạo Action mới mà chỉ
     * gọi nối tiếp 2 Action per-article/per-locale đã có sẵn trong 1 transaction — nếu tạo bản
     * dịch thất bại (vd trùng slug hiếm gặp), toàn bộ rollback, không để lại "vỏ" mồ côi.
     */
    public function store(Request $request, CreateArticleAction $articleAction, CreateTranslationAction $translationAction): RedirectResponse
    {
        $this->authorize('create', PostArticle::class);

        $data = ArticleData::from($this->validated($request));

        // §9 — permission "bật/tắt is_sponsored" tách khỏi post_article.edit thường: chặn ngay
        // cả khi request giả mạo gửi is_sponsored=true dù UI không hiện checkbox (test §14 mục 8).
        if ($data->is_sponsored) {
            abort_unless(auth()->user()->can('post_article.manage_sponsorship'), 403);
        }

        $title = $request->validate(['title' => ['required', 'string', 'max:300']])['title'];

        [$article, $translation] = DB::transaction(function () use ($data, $title, $articleAction, $translationAction) {
            $article     = $articleAction->handle($data);
            $translation = $translationAction->handle($article, $article->main_locale, TranslationData::from(['title' => $title]));

            return [$article, $translation];
        });

        return redirect()->route('backend.post.articles.edit', $article)
            ->with('success', 'Đã tạo bài viết — tiếp tục hoàn thiện nội dung bên dưới.')
            ->with('active_locale', $translation->locale);
    }

    public function show(PostArticle $article): View
    {
        $this->authorizeArticle($article, 'post_article.view');

        $article->load(['categories', 'tags', 'createdBy:id,name', 'translations.approvedBy:id,name']);

        return view('post::admin.articles.show', compact('article'));
    }

    public function edit(Request $request, PostArticle $article, GetCategoryTreeHandler $categoryTreeHandler, ArticleContentRenderer $renderer): View
    {
        $this->authorizeArticle($article, 'post_article.edit');

        $article->load(['categories', 'tags', 'ocopProducts', 'translations.contentBlocks.productBlock.items.product', 'translations.contentBlocks.productBlock.items.buttons', 'translations.contentBlocks.productBlock.buttons']);

        // Cây danh mục thật (cha/con/cháu...) — cùng lý do create() ở trên.
        $categoryTree = $categoryTreeHandler->handle(new GetCategoryTreeQuery());

        // spec/Province_Showcase_Technical_Specification.md §3.4.1 — lọc theo ward_code của bài
        // nếu có (chuyên sâu hơn), fallback province_code; bài chưa gắn tỉnh/phường nào → không
        // gợi ý sản phẩm nào cả (tránh danh sách toàn bộ sản phẩm quá dài). Đây chỉ là danh sách
        // hiển thị ban đầu — article-form.js gọi lại api/ocop-products/picker để load động mỗi
        // khi người dùng đổi lựa chọn tỉnh/phường trên <x-address-picker>.
        $ocopProducts = $article->ward_code || $article->province_code
            ? OcopProduct::published()
                ->when($article->ward_code, fn ($q) => $q->forWard($article->ward_code))
                ->when(! $article->ward_code && $article->province_code, fn ($q) => $q->forProvince($article->province_code))
                ->orderBy('name')
                ->get(['id', 'name', 'province_name', 'ward_name'])
            : collect();

        // Sản phẩm đã liên kết sẵn PHẢI luôn có mặt trong danh sách render (checked), kể cả khi
        // không khớp bộ lọc trên hoặc bài chưa gắn địa phương nào — nếu không, checkbox tương
        // ứng sẽ không tồn tại trong form và bị âm thầm xoá liên kết khi lưu lại.
        $ocopProducts = $ocopProducts
            ->concat($article->ocopProducts->whereNotIn('id', $ocopProducts->pluck('id')))
            ->sortBy('name')
            ->values();

        // Tab locale server-side (không SPA) — mỗi lần đổi tab load lại trang, tránh phải
        // làm cho post-block-composer.js/article-form.js (vốn giả định 1 form/1 composer duy
        // nhất mỗi trang) hỗ trợ nhiều instance cùng lúc.
        $locales = array_keys(config('post.locales'));
        $requestedLocale = $request->query('locale') ?? session('active_locale');
        $activeLocale = in_array($requestedLocale, $locales, true) ? $requestedLocale : $article->main_locale;

        $translation = $article->translation($activeLocale);
        $existingBlocks = $translation ? $renderer->toComposerPayload($translation) : [];

        return view('post::admin.articles.edit', compact('article', 'categoryTree', 'activeLocale', 'translation', 'existingBlocks', 'ocopProducts'));
    }

    public function update(Request $request, PostArticle $article, UpdateArticleAction $action): RedirectResponse
    {
        $this->authorizeArticle($article, 'post_article.edit');

        $data = ArticleData::from($this->validated($request));

        // §9 — gate cả 2 chiều "bật/tắt": đổi is_sponsored (bật mới hoặc tắt bài đang sponsored)
        // đều cần quyền riêng, không chỉ post_article.edit (test §14 mục 8).
        if ($data->is_sponsored || $article->is_sponsored) {
            abort_unless(auth()->user()->can('post_article.manage_sponsorship'), 403);
        }

        $action->handle($article, $data);

        return redirect()->route('backend.post.articles.edit', $article)
            ->with('success', 'Cập nhật bài viết thành công.');
    }

    public function destroy(PostArticle $article, DeleteArticleAction $action): RedirectResponse
    {
        $this->authorizeArticle($article, 'post_article.delete');

        $action->handle($article);

        return redirect()->route('backend.post.articles.index')
            ->with('success', 'Đã xoá bài viết.');
    }

    public function publishAll(PostArticle $article, PublishAllTranslationsAction $action): RedirectResponse
    {
        $this->authorizeArticle($article, 'post_article.publish');

        $action->handle($article);

        return back()->with('success', 'Đã xuất bản mọi bản dịch sẵn sàng.');
    }

    /**
     * §7.2/§9 — check permission trực tiếp (không qua $this->authorize('manageSponsorship', ...))
     * vì PostArticlePolicy::manageSponsorship() chỉ kiểm tra permission, không dùng đến field nào
     * của PostArticleTranslation — truyền $article->mainTranslation() (có thể null cho bài chưa
     * có bản dịch nào, dù is_sponsored=true đã bật từ card "Cài đặt chung") sẽ khiến Gate không
     * resolve được policy. Check permission thẳng, đúng pattern authorizeArticle() đã có.
     */
    public function removeSponsor(PostArticle $article, RemoveSponsorshipAction $action): RedirectResponse
    {
        abort_unless(auth()->user()->can('post_article.manage_sponsorship'), 403);

        $action->handle($article);

        return back()->with('success', 'Đã gỡ tài trợ khỏi bài viết.');
    }

    /**
     * PostArticle không còn 1 Policy method riêng (Policy giờ thao tác PostArticleTranslation) —
     * check quyền + ownership trực tiếp, giữ đúng logic cũ (chủ bài HOẶC có quyền publish).
     */
    private function authorizeArticle(PostArticle $article, string $permission): void
    {
        $user = auth()->user();

        // content_editor/content_head (Platform Approval Gateway — 2 tầng duyệt bài viết,
        // Hà Kiên) không có permission post_article.* nào (tài khoản organization_id=null,
        // permission team-scoped theo tổ chức) — nhưng cần xem/thao tác được để duyệt
        // (PostArticlePolicy::approve/publish/…).
        if ($user->isPlatformContentEditor() || $user->isPlatformContentHead()) {
            return;
        }

        abort_unless(
            $user->can($permission) && ($article->created_by === $user->id || $user->can('post_article.publish')),
            403,
        );
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'format'                  => ['required', 'in:' . implode(',', array_column(ArticleFormat::cases(), 'value'))],
            // spec/Media_Library_Technical_Specification.md §8 — chỉ dùng ở create form (form
            // sửa gắn ảnh trực tiếp qua FilePond context header, không qua field này).
            'cover_media_uuid'        => ['nullable', 'string'],
            'is_featured'             => ['boolean'],
            'main_locale'             => ['nullable', 'string', 'in:' . implode(',', array_keys(config('post.locales')))],
            'category_ids'            => ['array'],
            'category_ids.*'          => ['integer', 'exists:post_categories,id'],
            'is_primary_category_id'  => ['nullable', 'integer'],
            'tags'                    => ['nullable', 'string', 'max:500'],

            // spec/Province_Showcase_Technical_Specification.md §3.2/§6.3 — tuỳ chọn, không bắt
            // buộc (§0 mục 4) — không phá luồng viết bài hiện tại khi tác giả chưa chọn tỉnh.
            'province_code'           => ['nullable', 'string', 'size:2', 'exists:provinces,province_code'],
            'ward_code'               => ['nullable', 'string', 'size:5', 'exists:wards,ward_code'],
            // §3.4.1 — chỉ có ở form sửa bài viết, create form không gửi field này (mảng rỗng mặc định).
            'ocop_product_ids'        => ['array'],
            'ocop_product_ids.*'      => ['integer', 'exists:ocop_products,id'],

            // spec/dac-ta-ky-thuat-bai-viet-tai-tro.md §6.1/§10 — rule string thuần, KHÔNG dựa
            // vào attribute Spatie Data trên ArticleData (không có tác dụng validate thật ở đây).
            'is_sponsored'            => ['boolean'],
            'sponsor_name'            => ['required_if:is_sponsored,1', 'nullable', 'string', 'max:255'],
            'sponsor_logo_url'        => ['nullable', 'string', 'max:500'],
            'sponsor_label'           => ['required_if:is_sponsored,1', 'nullable', Rule::enum(SponsorLabel::class)],
            'campaign_code'           => ['nullable', 'string', 'max:50'],
            'sponsored_start_date'    => ['nullable', 'date'],
            'sponsored_end_date'      => ['nullable', 'date', 'after_or_equal:sponsored_start_date'],
        ]);
    }
}
