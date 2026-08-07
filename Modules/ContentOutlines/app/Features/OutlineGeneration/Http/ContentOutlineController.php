<?php

namespace Modules\ContentOutlines\Features\OutlineGeneration\Http;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Modules\ContentFoundation\Actions\ListCategoryFoundationsAction;
use Modules\ContentOutlines\Features\ArticleDrafting\Actions\BuildArticleDraftPromptAction;
use Modules\ContentOutlines\Features\ArticleDrafting\Actions\SaveApprovedOutlineAndBuildArticlePromptAction;
use Modules\ContentOutlines\Features\ArticleDrafting\Http\Requests\StoreApprovedOutlineRequest;
use Modules\ContentOutlines\Features\ArticleReview\Actions\BuildArticleReviewPromptAction;
use Modules\ContentOutlines\Features\ArticleReview\Actions\SaveDraftedArticleAndBuildReviewPromptAction;
use Modules\ContentOutlines\Features\ArticleReview\Http\Requests\StoreDraftedArticleRequest;
use Modules\ContentOutlines\Features\OutlineGeneration\Actions\BuildContentOutlinePromptAction;
use Modules\ContentOutlines\Features\OutlineGeneration\Actions\CreateContentOutlineAction;
use Modules\ContentOutlines\Features\OutlineGeneration\Actions\LinkContentOutlineToArticleAction;
use Modules\ContentOutlines\Features\OutlineGeneration\Actions\RegenerateContentOutlinePromptAction;
use Modules\ContentOutlines\Features\OutlineGeneration\Data\ContentOutlineInputData;
use Modules\ContentOutlines\Features\OutlineGeneration\Http\Requests\StoreContentOutlineRequest;
use Modules\ContentOutlines\Features\OutlineGeneration\Http\Requests\UpdateContentOutlineRequest;
use Modules\ContentOutlines\Models\ContentOutline;
use Modules\Post\Models\PostArticle;

/**
 * spec/ContentOutlines_Technical_Specification.md §6/§7 — gate phẳng bằng middleware
 * 'can:content_outlines.use' ở routes/web.php, KHÔNG Policy riêng theo model (§2.1 — không có
 * owner-based ACL, mọi người có permission xem/sửa/xoá được MỌI outline).
 */
class ContentOutlineController extends Controller
{
    public function index(): View
    {
        return view('contentoutlines::index');
    }

    public function create(ListCategoryFoundationsAction $listCategoryFoundations): View
    {
        return view('contentoutlines::create', [
            'categories' => $listCategoryFoundations->handle(withFoundationDetails: false),
        ]);
    }

    public function store(StoreContentOutlineRequest $request, CreateContentOutlineAction $action): RedirectResponse
    {
        $data = ContentOutlineInputData::from($request->toData());
        $outline = $action->handle($data, $request->user()->id);

        return redirect()->route('backend.contentoutlines.show', $outline)
            ->with('success', 'Đã sinh dàn ý nội dung — sao chép prompt bên dưới để dán sang AI.');
    }

    /**
     * §4.1/§4.5 (v1.1) — cảnh báo độ dài (soft warning, KHÔNG chặn) + render Markdown preview
     * qua league/commonmark (`Str::markdown()`, đã có sẵn trong vendor qua dependency Laravel,
     * KHÔNG thêm thư viện JS mới) — collapsible theo từng khối "## " thực hiện ở client
     * (content-outlines.js) trên chính HTML này, không cần server tách khối.
     */
    public function show(ContentOutline $outline): View
    {
        $outline->load(['category', 'linkedArticle.translations', 'createdBy']);

        $promptWordCount = BuildContentOutlinePromptAction::estimateWordCount($outline->generated_prompt);

        // §4.17 (v1.14) — cùng cơ chế soft-warning/Markdown-preview với prompt outline, áp cho
        // article_draft_prompt (rỗng nếu chưa sinh "Bước 2" lần nào — Str::markdown('') → '').
        $articlePromptWordCount = BuildArticleDraftPromptAction::estimateWordCount((string) $outline->article_draft_prompt);

        // §4.20 (v1.16) — cùng cơ chế soft-warning/Markdown-preview với 2 prompt trước, áp cho
        // review_prompt (rỗng nếu chưa sinh "Bước 3" lần nào).
        $reviewPromptWordCount = BuildArticleReviewPromptAction::estimateWordCount((string) $outline->review_prompt);

        return view('contentoutlines::show', [
            'outline' => $outline,
            'promptWordCount' => $promptWordCount,
            'promptIsLong' => $promptWordCount > BuildContentOutlinePromptAction::WORD_COUNT_WARNING_THRESHOLD,
            'promptHtml' => Str::markdown($outline->generated_prompt),
            'articlePromptWordCount' => $articlePromptWordCount,
            'articlePromptIsLong' => $articlePromptWordCount > BuildArticleDraftPromptAction::WORD_COUNT_WARNING_THRESHOLD,
            'articlePromptHtml' => Str::markdown((string) $outline->article_draft_prompt),
            'reviewPromptWordCount' => $reviewPromptWordCount,
            'reviewPromptIsLong' => $reviewPromptWordCount > BuildArticleReviewPromptAction::WORD_COUNT_WARNING_THRESHOLD,
            'reviewPromptHtml' => Str::markdown((string) $outline->review_prompt),
        ]);
    }

    public function edit(ContentOutline $outline, ListCategoryFoundationsAction $listCategoryFoundations): View
    {
        return view('contentoutlines::edit', [
            'outline' => $outline,
            'categories' => $listCategoryFoundations->handle(withFoundationDetails: false),
        ]);
    }

    public function update(UpdateContentOutlineRequest $request, ContentOutline $outline, RegenerateContentOutlinePromptAction $action): RedirectResponse
    {
        $data = ContentOutlineInputData::from($request->toData());
        $action->handle($outline, $data, $request->user()->id);

        return redirect()->route('backend.contentoutlines.show', $outline)
            ->with('success', 'Đã sinh lại prompt theo input mới.');
    }

    public function destroy(ContentOutline $outline): RedirectResponse
    {
        $outline->delete();

        return redirect()->route('backend.contentoutlines.index')->with('success', 'Đã xoá dàn ý.');
    }

    /**
     * §4.17 (v1.14) — "Bước 2": lưu outline đã duyệt (dán tay từ AI ngoài) + sinh prompt viết bài
     * nhúng SẴN outline đó — vẫn KHÔNG gọi AI Provider nào (§0 mục 1), chỉ sinh thêm 1 đoạn text.
     * Ghi đè `article_draft_prompt` cũ nếu đã có (không versioning, cùng §4.2).
     */
    public function generateArticlePrompt(StoreApprovedOutlineRequest $request, ContentOutline $outline, SaveApprovedOutlineAndBuildArticlePromptAction $action): RedirectResponse
    {
        $action->handle($outline, $request->validated('approved_outline'), $request->user()->id);

        return redirect()->route('backend.contentoutlines.show', $outline)
            ->with('success', 'Đã sinh prompt viết bài — sao chép bên dưới để dán sang 1 lượt chat AI mới.');
    }

    /**
     * §4.20 (v1.16) — "Bước 3": lưu bài viết đã dán tay (từ AI ngoài chạy `article_draft_prompt`,
     * hoặc viết tay) + sinh prompt soát lỗi/sửa nhúng SẴN bài đó — vẫn KHÔNG gọi AI Provider nào
     * (§0 mục 1), chỉ sinh thêm 1 đoạn text. Ghi đè `review_prompt` cũ nếu đã có (không versioning).
     */
    public function generateReviewPrompt(StoreDraftedArticleRequest $request, ContentOutline $outline, SaveDraftedArticleAndBuildReviewPromptAction $action): RedirectResponse
    {
        $action->handle($outline, $request->validated('drafted_article'), $request->user()->id);

        return redirect()->route('backend.contentoutlines.show', $outline)
            ->with('success', 'Đã sinh prompt soát lỗi/sửa bài — sao chép bên dưới để dán sang 1 lượt chat AI mới.');
    }

    /**
     * §1 — liên kết THAM CHIẾU thủ công tới 1 PostArticle đã có, KHÔNG sinh/copy nội dung. Form
     * v1 nhận thẳng UUID bài viết (dán từ URL sửa bài viết đó) — chưa có picker tìm-khi-gõ, xem
     * spec §8 "Ngoài phạm vi" (để mở cho vòng tinh chỉnh UX sau).
     */
    public function linkArticle(Request $request, ContentOutline $outline, LinkContentOutlineToArticleAction $action): RedirectResponse
    {
        $validated = $request->validate([
            'post_article_uuid' => ['nullable', 'string', 'uuid', 'exists:post_articles,uuid'],
        ]);

        $articleId = $validated['post_article_uuid']
            ? PostArticle::where('uuid', $validated['post_article_uuid'])->value('id')
            : null;

        $action->handle($outline, $articleId, $request->user()->id);

        return redirect()->route('backend.contentoutlines.show', $outline)
            ->with('success', $articleId ? 'Đã gắn dàn ý vào bài viết.' : 'Đã gỡ liên kết bài viết.');
    }
}
