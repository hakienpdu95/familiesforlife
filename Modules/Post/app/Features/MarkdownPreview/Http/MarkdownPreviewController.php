<?php

namespace Modules\Post\Features\MarkdownPreview\Http;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Post\Models\PostArticle;
use Modules\Post\Models\PostArticleTranslation;
use Modules\Post\Support\ArticleContentRenderer;

/**
 * Công cụ QA nội bộ cho admin — xem trước ĐÚNG bản Markdown mà content negotiation
 * (`Accept: text/markdown`, spec/Markdown_Content_Negotiation_Technical_Specification.md §3)
 * sẽ trả về cho 1 bài viết, không cần tự set header bằng tay (curl/Postman) để kiểm tra.
 *
 * KHÔNG thuộc phạm vi spec (spec chỉ sửa hành vi negotiation, không định nghĩa trang admin nào)
 * — bổ sung theo yêu cầu người dùng, đọc-chỉ-đọc (không ghi DB), tái dùng nguyên vẹn
 * `ArticleContentRenderer::renderMarkdownDocument()` — cùng 1 hàm mà
 * `PublicArticleController::showMarkdown()` gọi, nên bản preview LUÔN khớp 100% với response
 * thật của content negotiation.
 */
class MarkdownPreviewController extends Controller
{
    public function index(Request $request, ArticleContentRenderer $renderer): View
    {
        $this->authorize('viewAny', PostArticle::class);

        $validated = $request->validate([
            'translation_id' => ['nullable', 'integer'],
        ]);

        $translation = null;
        $markdown = null;
        $canonicalUrl = null;

        if ($translationId = $validated['translation_id'] ?? null) {
            $translation = PostArticleTranslation::published()->find($translationId);

            if ($translation) {
                $markdown = $renderer->renderMarkdownDocument($translation);
                $canonicalUrl = route('post.public.article', ['slug' => $translation->slug, 'id' => $translation->id]);
            }
        }

        return view('post::admin.markdown-preview.index', [
            'translation' => $translation,
            'markdown' => $markdown,
            'canonicalUrl' => $canonicalUrl,
        ]);
    }
}
