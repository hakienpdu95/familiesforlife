# Content Negotiation Markdown cho Bài viết
**Đặc tả Kỹ thuật Chi tiết — CHƯA triển khai, chờ duyệt trước khi code**

**Phiên bản:** 2.1
**Ngày:** 2026-08-08
**Framework:** Laravel 13 (PHP 8.4) + NWIDART Modules + Lorisleiva Actions
**Module chạm tới:** `Modules/Post` (không tạo module mới)
**Nguồn:**
- Moz Whiteboard Friday "The SEO's Guide to Agentic Commerce" (Miracle Inameti-Archibong) — lý do phục vụ Markdown cho agent (tiết kiệm token).
- `acceptmarkdown.com/recipes/laravel` + `acceptmarkdown.com/guides/generating-markdown` — pattern parse `Accept` header đúng chuẩn, khuyến nghị cache, cảnh báo `Vary: Accept`.
- 1 trang định nghĩa thuật ngữ "Markdown content negotiation" (David Garcia) — chỉ rõ **URL riêng KHÔNG PHẢI content negotiation thật** theo đúng nghĩa HTTP (client nêu preference qua `Accept`, server trả CÙNG 1 URL, đổi representation, kèm `Vary: Accept`) — dẫn tới quyết định LÀM LẠI TOÀN BỘ ở v2.0 (xem changelog).
- `ekamoira.com/blog/how-to-serve-markdown-to-ai-crawlers-content-negotiation-token-economics-guide` — số liệu token economics, xác nhận Accept-header negotiation đúng chuẩn (khác cloaking mà John Mueller/Google lo ngại ở URL riêng), thực trạng crawler nào THẬT SỰ gửi `Accept: text/markdown`, khuyến nghị `Cache-Control` + `<link rel="alternate">` tự trỏ về CHÍNH nó — dẫn tới các bổ sung ở v2.1 (xem changelog).

> **v2.0 (đổi kiến trúc — thay hẳn hướng tiếp cận, không phải vá thêm):** v1.x (1.0-1.2) thiết kế
> theo hướng **URL riêng** (`{slug}-d{id}.md` cạnh `{slug}-d{id}.html`) — bị chỉ ra ĐÚNG là **không
> phải "content negotiation"** theo nghĩa HTTP chuẩn (RFC 9110): 1 URL riêng là "second address for
> the same content", còn negotiation thật là CÙNG 1 URL, đổi representation theo `Accept` header,
> kèm `Vary: Accept`. Sau khi cân nhắc, người dùng CHỌN làm lại đúng chuẩn (Hướng B, không chỉ đổi
> tên gọi) — **XOÁ HẲN** route `.md` riêng (§2/§5/§6 của v1.x không còn áp dụng), sửa TRỰC TIẾP
> `PublicArticleController::show()` (route `.html` hiện có) để tự chọn representation theo
> `Accept` header — chỉ còn ĐÚNG 1 URL canonical duy nhất cho mỗi bài viết. Phần logic convert
> từng loại `ContentBlockType` sang Markdown (`ArticleContentRenderer::renderMarkdown()`) GIỮ
> NGUYÊN không đổi gì — đó là phần độc lập với việc chọn URL riêng hay negotiation.
>
> **v2.1 (bổ sung sau khi đối chiếu ekamoira.com — KHÔNG đổi kiến trúc v2.0):** (1) đảo ngược 1
> quyết định v2.0 — `<link rel="alternate" type="text/markdown">` VẪN nên có, nhưng khác nghĩa với
> v1.x: trỏ về CHÍNH URL hiện tại (self-reference) để khai báo "URL này có bản Markdown lấy được
> qua Accept header", không phải trỏ sang 1 URL khác (§5); (2) thêm `Cache-Control` cho response
> Markdown, phục vụ CDN/edge cache — trước đây chỉ có cache tầng ứng dụng (§3); (3) thêm số liệu
> token economics cụ thể vào §1 làm căn cứ; (4) thêm ghi chú "HIỆU CHỈNH KỲ VỌNG" (§1) — hiện tại
> CHỈ xác nhận Claude Code/OpenCode chủ động gửi `Accept: text/markdown`; GPTBot/PerplexityBot/
> Gemini/Googlebot KHÔNG chủ động gửi header này — lợi ích thực tế trước mắt giới hạn ở nhóm agent
> đã hỗ trợ, KHÔNG kỳ vọng ảnh hưởng ngay tới crawler traffic nói chung.

---

## 0. Quyết định đã chốt

| Chủ đề | Quyết định spec này | Lý do |
|---|---|---|
| **KHÔNG có URL `.md` riêng — chỉ 1 URL canonical** | Sửa trực tiếp `PublicArticleController::show()` (route `{slug}-d{id}.html` hiện có) — không thêm route nào | Đúng định nghĩa "content negotiation" (client nêu preference qua header, server trả CÙNG URL) — khác hẳn quyết định v1.x đã bị chỉ ra là sai tên gọi |
| **Parse `Accept` header đúng chuẩn RFC 9110 §12.5.1 — KHÔNG so khớp chuỗi ngây thơ** | Trait `NegotiatesMarkdown` (`Modules/Post/app/Support/`) — xử lý q-value, specificity (`type/subtype` > `type/*` > `*/*`), loại bỏ `q=0`, tiebreak theo thứ tự khai báo trong `$produces` khi mọi thứ bằng nhau | `str_contains($accept, 'text/markdown')` SAI khi client gửi `Accept: text/html;q=0.9, text/markdown;q=0.1` (client vẫn ưu tiên HTML hơn hẳn dù CÓ nhắc tới markdown) — đây chính xác là lỗi phổ biến được cảnh báo ở nguồn `acceptmarkdown.com`. Test kỹ với Accept header thật của trình duyệt (`text/html,application/xhtml+xml,...,*/*;q=0.8`) phải LUÔN ra HTML |
| **`Vary: Accept` bắt buộc trên CẢ 2 representation** | Thêm header này vào cả response HTML (`response()->view(...)->header('Vary', 'Accept')`) lẫn response Markdown | Không có header này, CDN/reverse-proxy/trình duyệt có thể cache SAI — trả bản Markdown cho 1 trình duyệt thường sau khi đã cache cho 1 agent trước đó (hoặc ngược lại). Đây là lỗi ĐƯỢC NÊU RÕ là phổ biến nhất khi làm content negotiation không đầy đủ |
| **Redirect-format (`$article->isRedirect()`) — hành vi GIỐNG NHAU bất kể `Accept`** | Check redirect đặt TRƯỚC bước gọi `prefersMarkdown()` — luôn redirect thẳng ra `redirect_url`, không có "bản Markdown của 1 redirect" | Redirect là quyết định Ở TẦNG URL (mã 302, không có `Content-Type` cho body để negotiate) — không phải quyết định Ở TẦNG RENDER. Giữ nguyên hành vi đã có, không mở rộng phạm vi thay đổi |
| **Bỏ qua `IncrementArticleViewCountAction`/`RecordArticleViewEventAction` cho request Markdown** | Chỉ gọi 2 Action này ở nhánh HTML, KHÔNG gọi ở nhánh Markdown | Request có `Accept: text/markdown` là tín hiệu TỰ KHAI BÁO rõ ràng "đây là agent/máy", đáng tin hơn hẳn việc dò User-Agent — không nên tính vào `view_count`/co-occurrence vốn dùng để đo hành vi ĐỘC GIẢ THẬT (Related Posts engine dựa vào tín hiệu này, xem `spec/Related_Posts_Engine_Technical_Specification.md`) |
| **Cache `renderMarkdown()`, key tự-hết-hạn theo `updated_at`** | `Cache::remember("post:{$translation->id}:markdown:v1:{$translation->updated_at?->timestamp}", now()->addDay(), ...)` | Giữ nguyên quyết định đã chốt ở v1.2 (đã verify `UpdateTranslationAction`/`RestoreArticleVersionAction` luôn `update()` translation trước khi đổi content-block trong cùng transaction, nên key tự đổi đúng lúc) — không liên quan gì tới việc đổi URL riêng hay negotiation, giữ nguyên |
| **`<link rel="alternate" type="text/markdown">` — VẪN thêm, nhưng TỰ TRỎ về chính URL đó (v2.1, đảo ngược quyết định v2.0)** | `<link rel="alternate" type="text/markdown" href="{{ $canonicalUrl }}">` trong `<head>` — `href` GIỐNG HỆT URL trang đang xem | Đây không phải "khai báo URL khác" như v1.x (lý do v2.0 xoá) — mà là khai báo NĂNG LỰC negotiation của chính URL này, giúp tool/crawler biết trước có thể xin bản Markdown qua `Accept` mà không cần thử. Nguồn `ekamoira.com` xác nhận pattern tự-trỏ này vẫn có giá trị dù dùng content negotiation thật |
| **Không cần lo "duplicate content"/sitemap nữa** | Bỏ hẳn mối quan tâm này (đã có ở v1.x §0) | Chỉ có 1 URL, không có khái niệm trang thứ 2 cạnh tranh SERP — negotiation triệt tiêu HẲN vấn đề này thay vì phải "giải thích tại sao không tính là duplicate" như thiết kế cũ |
| **`Cache-Control` cho response Markdown (v2.1, thêm mới)** | `Cache-Control: public, max-age=3600` trên response Markdown (song song với `Cache::remember()` tầng ứng dụng đã có) | Nguồn `ekamoira.com` nêu rõ đây là lớp cache KHÁC — cache tầng ứng dụng tránh tính lại `renderMarkdown()` mỗi request, `Cache-Control` cho phép CDN/edge/proxy tự phục vụ mà KHÔNG cần chạm tới server — 2 lớp không thay thế nhau. Không thêm cho response HTML (ngoài phạm vi sửa đổi hiện có, tránh mở rộng scope không cần thiết) |
| **`llms.txt` — đổi hướng dẫn agent** | Thay dòng ghi chú cũ ("đổi đuôi .html→.md") bằng: "gửi `Accept: text/markdown` khi tải bất kỳ URL bài viết nào để nhận bản Markdown" | Đúng cách dùng thật của content negotiation — agent không cần biết URL pattern nào cả, chỉ cần set đúng header |
| **Không có Accept header (client không chuẩn/cũ)** | `pickType()` trả về phần tử ĐẦU TIÊN trong `$produces` (luôn là `text/html`) làm mặc định | An toàn — không suy đoán "im lặng = muốn Markdown", giữ hành vi hiện tại cho MỌI client không khai báo rõ |
| **Khối Comparison → bảng Markdown GFM thật; Testimonial/Citation → blockquote; Text → `league/html-to-markdown` + `absolutizeUrls()` + `demoteH1()`** | Giữ NGUYÊN logic `renderMarkdown()` đã thiết kế ở v1.1/v1.2, không đổi gì | Phần này độc lập hoàn toàn với việc chọn URL riêng hay negotiation — đã đối chiếu kỹ 2 lần trước (§3), không cần làm lại |
| **Thêm dependency `league/html-to-markdown`** | `composer require league/html-to-markdown` | Giữ nguyên lý do đã chốt ở v1.0 — không đổi |

---

## 1. Giới thiệu & Mục tiêu

Bài viết công khai hiện chỉ có 1 dạng biểu diễn: HTML đầy đủ. AI agent xử lý HTML tốn token hơn hẳn Markdown cho cùng lượng thông tin. Số liệu cụ thể (nguồn `ekamoira.com`, đo thực tế chứ không phải ước lượng lý thuyết):

- 1 bài blog: 16.180 token HTML → 3.150 token Markdown (**giảm ~80%**).
- 1 trang thương mại điện tử (heading/nav/thuộc tính lặp lại nhiều): ước tính 40.000 → ~2.000 token (**giảm ~95%**).
- 1 thẻ heading đơn lẻ: ~12-15 token dạng HTML so với ~3 token dạng Markdown.
- RAG (retrieval-augmented generation) trích xuất thông tin từ Markdown chính xác hơn HTML ~35% trong 1 thử nghiệm được dẫn.

Đòn bẩy kinh tế: context window của AI agent có hạn — nội dung càng ít token, agent càng lấy được nhiều nguồn cùng lúc trong 1 lượt truy vấn, tăng khả năng site được trích dẫn.

Giải pháp ĐÚNG CHUẨN: khi request tới `{slug}-d{id}.html` có header `Accept: text/markdown` được ưu tiên hơn `text/html`, server trả về Markdown thuần (không layout/nav/script) tại **CHÍNH URL ĐÓ**, kèm `Vary: Accept`. Không có URL thứ 2 nào — 1 bài viết luôn có đúng 1 địa chỉ.

> **HIỆU CHỈNH KỲ VỌNG (v2.1):** không phải mọi AI crawler đều chủ động gửi `Accept: text/markdown`
> hiện nay. Theo `ekamoira.com`: **Claude Code và OpenCode xác nhận có gửi** header này; **ChatGPT
> agent CHƯA gửi** (nhận diện được qua header `Signature-Agent: 'https://chatgpt.com'` thay vào đó
> — ngoài phạm vi spec này, không xử lý); **Googlebot không xin Markdown** (đúng hành vi mong đợi —
> Google index HTML); **Perplexity/Gemini được lợi nếu có nhưng chưa chủ động xin qua header**. Vì
> vậy lợi ích trước mắt của tính năng này giới hạn ở nhóm agent ĐÃ hỗ trợ chuẩn Accept-header — và
> ở chính module `Mcp` (spec riêng, `spec/MCP_Server_Technical_Specification.md`) khi tool
> `get_article` gọi `renderMarkdown()` trực tiếp, không phụ thuộc crawler nào có hỗ trợ hay không.
> KHÔNG kỳ vọng thay đổi ngay traffic/citation từ GPTBot/PerplexityBot nói chung.

---

## 2. `NegotiatesMarkdown` — parse `Accept` header đúng chuẩn

`Modules/Post/app/Support/NegotiatesMarkdown.php`

```php
namespace Modules\Post\Support;

use Illuminate\Http\Request;

/**
 * Parse `Accept` header ĐÚNG chuẩn RFC 9110 §12.5.1 (q-value, specificity, loại bỏ q=0) — KHÔNG
 * so khớp chuỗi ngây thơ. Dùng chung được cho bất kỳ Controller nào cần content negotiation
 * (hiện chỉ PublicArticleController dùng, nhưng đặt ở Support cấp module để tái dùng dễ nếu
 * Video/Playlist cần sau này — xem §8).
 */
trait NegotiatesMarkdown
{
    protected function prefersMarkdown(Request $request): bool
    {
        return $this->pickType($request, ['text/html', 'text/markdown']) === 'text/markdown';
    }

    /**
     * Trả về type PHÙ HỢP NHẤT trong $produces theo Accept header — ưu tiên q cao hơn, rồi tới
     * độ cụ thể (type/subtype > type/* > * / *), rồi tới thứ tự khai báo trong $produces khi mọi
     * thứ bằng nhau (VD "Accept: * / *" từ curl mặc định → luôn chọn phần tử ĐẦU TIÊN, tức HTML,
     * KHÔNG suy đoán agent muốn Markdown chỉ vì "chấp nhận được").
     *
     * @param  string[]  $produces  Danh sách type hỗ trợ, THỨ TỰ = độ ưu tiên khi tie-break.
     */
    protected function pickType(Request $request, array $produces): ?string
    {
        $accept = $request->header('Accept');

        if (! $accept) {
            return $produces[0] ?? null;
        }

        $ranges = $this->parseAcceptHeader($accept);

        $best = null;
        $bestScore = null;

        foreach ($produces as $index => $type) {
            [$typePart, $subtypePart] = explode('/', $type, 2);

            foreach ($ranges as $range) {
                if ($range['q'] <= 0.0) {
                    continue; // q=0 — client TỪ CHỐI loại này tường minh
                }

                $specificity = match (true) {
                    $range['type'] === $typePart && $range['subtype'] === $subtypePart => 2,
                    $range['type'] === $typePart && $range['subtype'] === '*' => 1,
                    $range['type'] === '*' && $range['subtype'] === '*' => 0,
                    default => null,
                };

                if ($specificity === null) {
                    continue;
                }

                $score = [$range['q'], $specificity, -$index];

                if ($bestScore === null || $score > $bestScore) {
                    $bestScore = $score;
                    $best = $type;
                }
            }
        }

        return $best;
    }

    /** @return array<int, array{type: string, subtype: string, q: float}> */
    private function parseAcceptHeader(string $accept): array
    {
        $ranges = [];

        foreach (explode(',', $accept) as $entry) {
            $parts = explode(';', trim($entry));
            $mediaRange = strtolower(trim(array_shift($parts)));

            if (! str_contains($mediaRange, '/')) {
                continue; // entry hỏng — Accept header do client tự gửi, không throw, bỏ qua
            }

            [$type, $subtype] = explode('/', $mediaRange, 2);

            $q = 1.0;
            foreach ($parts as $param) {
                if (preg_match('/^\s*q\s*=\s*([\d.]+)\s*$/i', $param, $m)) {
                    $q = (float) $m[1];
                }
            }

            $ranges[] = ['type' => trim($type), 'subtype' => trim($subtype), 'q' => $q];
        }

        return $ranges;
    }
}
```

---

## 3. `PublicArticleController::show()` — sửa trực tiếp (route `.html` KHÔNG đổi)

```php
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Modules\Post\Support\NegotiatesMarkdown;

class PublicArticleController extends Controller
{
    use NegotiatesMarkdown;

    public function show(
        Request $request,
        string $slug,
        IncrementArticleViewCountAction $viewAction,
        RecordArticleRedirectClickAction $clickAction,
        RecordArticleViewEventAction $viewEventAction,
        GetRelatedArticlesHandler $relatedHandler,
        ArticleContentRenderer $renderer,
        ArticleStructuredDataBuilder $structuredDataBuilder,
    ): View|RedirectResponse|Response {
        $translation = PostArticleTranslation::published()
            ->where('locale', config('post.default_locale'))
            ->where('slug', $slug)
            ->with([
                'article.categories', 'article.tags', 'article.createdBy.authorProfile',
                'contentBlocks.productBlock.items.product' => fn ($q) => $q->publicEmbed(),
                'contentBlocks.productBlock.items.buttons', 'contentBlocks.productBlock.buttons',
                'contentBlocks.faqBlock.items', 'faqBlocks.items',
                'contentBlocks.howtoBlock.steps', 'howtoBlocks.steps',
                'contentBlocks.comparisonBlock.columns', 'contentBlocks.comparisonBlock.rows',
            ])
            ->first();

        abort_unless($translation, 404);

        $article = $translation->article;

        // Redirect-format — GIỐNG HỆT hành vi hiện có, KHÔNG phụ thuộc Accept header (§0: redirect
        // là quyết định tầng URL, không phải tầng render/negotiate).
        if ($article?->isRedirect() && $article->redirect_url) {
            $viewAction->handle($translation);
            $clickAction->handle($article);

            return redirect()->away($article->redirect_url);
        }

        // Nhánh Markdown — tách sớm TRƯỚC khi tính view_count/related (§0: request tự khai báo
        // "đây là agent", không tính vào các số liệu đo hành vi ĐỘC GIẢ THẬT).
        if ($this->prefersMarkdown($request)) {
            return $this->showMarkdown($translation, $renderer);
        }

        $viewAction->handle($translation);
        $viewEventAction->handle($article->id);

        $related = $relatedHandler->handle(new GetRelatedArticlesQuery(
            articleId: $article->id,
            locale: $translation->locale,
            limit: (int) config('post.related_posts.max_results', 6),
        ));

        $canonicalUrl = route('post.public.article', ['slug' => $translation->slug, 'id' => $translation->id]);

        // response()->view() thay vì return view() trực tiếp — CẦN để gắn thêm header Vary (§0).
        return response()->view('post::public.article', [
            'translation'     => $translation,
            'article'         => $article,
            'locale'          => $translation->locale,
            'content'         => $renderer->render($translation),
            'relatedArticles' => $related,
            'canonicalUrl'    => $canonicalUrl,
            'structuredData'  => $structuredDataBuilder->build($article, $translation, $canonicalUrl),
        ])->header('Vary', 'Accept');
    }

    private function showMarkdown(PostArticleTranslation $translation, ArticleContentRenderer $renderer): Response
    {
        $canonicalUrl = route('post.public.article', ['slug' => $translation->slug, 'id' => $translation->id]);
        $description = $translation->seo_description ?: $translation->direct_answer ?: $translation->excerpt;

        $header = "# {$translation->title}\n\n";
        if ($description) {
            $header .= "> {$description}\n\n";
        }
        $header .= "Nguồn: {$canonicalUrl}\n";
        if ($translation->updated_at) {
            $header .= 'Cập nhật: '.$translation->updated_at->format('d/m/Y')."\n";
        }
        $header .= "\n---\n\n";

        return response($header.$renderer->renderMarkdown($translation), 200)
            ->header('Content-Type', 'text/markdown; charset=UTF-8')
            ->header('Vary', 'Accept')
            // Lớp cache KHÁC với Cache::remember() trong renderMarkdown() (§4) — đây là chỉ dẫn
            // cho CDN/edge/reverse-proxy tự phục vụ mà KHÔNG cần chạm tới Laravel, 2 lớp không
            // thay thế nhau (nguồn ekamoira.com). Không thêm cho response HTML — ngoài phạm vi
            // sửa đổi hiện có của spec này.
            ->header('Cache-Control', 'public, max-age=3600');
    }
}
```

> **Route `Modules/Post/routes/web.php` — KHÔNG đổi gì**, `Request $request` được Laravel tự inject qua service container theo type-hint, không cần khai trong route.

---

## 4. `ArticleContentRenderer::renderMarkdown()` — giữ nguyên logic v1.1/v1.2

Không đổi so với bản trước — chỉ tóm tắt lại các quyết định đã chốt (chi tiết code xem lịch sử spec, không lặp lại ở đây để tránh lệch nếu sửa 1 chỗ quên chỗ kia):

- `match ($block->type)` liệt kê đủ mọi `ContentBlockType`, nhánh `default` trả ghi chú thấy được + log warning, không fallback ngầm định.
- `Text` → `league/html-to-markdown` sau khi `absolutizeUrls()` (URL root-relative → tuyệt đối) và `demoteH1()` (tránh 2 H1 trong 1 response — Controller đã tự thêm `# {title}`).
- `Comparison` → bảng GFM thật, có `array_pad`/`array_slice` phòng thủ lớp 2 dù đã validate số cột lúc ghi.
- `Testimonial`/`Citation` → blockquote.
- `Product` → tên + giá + mô tả ngắn (`display_description`).
- Bọc `Cache::remember()`, key `"post:{$translation->id}:markdown:v1:{$translation->updated_at?->timestamp}"`, TTL 1 ngày — tự hết hạn khi bài được lưu lại (đã verify `UpdateTranslationAction`/`RestoreArticleVersionAction`).

---

## 5. Discoverability — `<link rel="alternate">` (v2.1) & `llms.blade.php`

**`article.blade.php` — thêm 1 dòng trong `<head>`, TỰ TRỎ về chính URL đang xem (khác hẳn nghĩa
`<link rel="alternate">` ở thiết kế v1.x đã bị xoá — lúc đó trỏ sang 1 URL `.md` khác):**

```blade
<link rel="alternate" type="text/markdown" href="{{ $canonicalUrl }}">
```

`$canonicalUrl` đã có sẵn trong dữ liệu truyền cho view (§3) — không cần tính thêm gì. Mục đích:
khai báo trước cho tool/crawler "URL này có thể lấy dạng Markdown qua `Accept` header", không cần
thử mù. Nguồn `ekamoira.com` xác nhận pattern tự-trỏ này vẫn có giá trị dưới content negotiation
thật (khác hẳn việc dùng thẻ này để trỏ sang 1 trang khác — đó mới là điều v2.0 đã đúng khi loại bỏ).

**`llms.blade.php` — đổi hướng dẫn:**

```blade
## Bản Markdown cho AI agent

Mọi URL bài viết hỗ trợ content negotiation — gửi header `Accept: text/markdown` khi tải trang để
nhận bản Markdown thuần (không layout/nav/script) thay vì HTML, tại ĐÚNG cùng 1 URL.
```

> Lưu ý (v2.1, nguồn `ekamoira.com`): 1 nghiên cứu trên 300.000 domain không tìm thấy tác động đo
> được của `llms.txt` tới tần suất được LLM trích dẫn — file này chỉ có giá trị KHAI BÁO/tài liệu,
> không nên kỳ vọng tự nó tăng traffic/citation. Vẫn giữ vì chi phí duy trì gần như bằng 0 và không
> có tác dụng phụ, nhưng không đầu tư thêm công sức mở rộng nó.

---

## 6. Kiểm thử bắt buộc

- `GET {slug}-d{id}.html` không kèm `Accept` header → HTML (mặc định, §0).
- `Accept: text/markdown` → `Content-Type: text/markdown; charset=UTF-8`, `Vary: Accept`, `Cache-Control: public, max-age=3600` (v2.1), không kèm layout/nav/footer/script.
- HTML response → có thẻ `<link rel="alternate" type="text/markdown" href="...">` trong `<head>`, `href` PHẢI GIỐNG HỆT `canonicalUrl` của chính trang đang xem (v2.1 — test sai nếu `href` trỏ sang URL khác, vì đó là quay lại đúng lỗi thiết kế v1.x đã bị xoá).
- `Accept: text/html` → HTML, `Vary: Accept`.
- `Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,*/*;q=0.8` (Accept header THẬT của trình duyệt) → PHẢI ra HTML, không được nhầm thành Markdown.
- `Accept: text/markdown;q=0.9, text/html;q=0.1` → Markdown (client ưu tiên Markdown rõ ràng).
- `Accept: text/html;q=0.9, text/markdown;q=0.1` → HTML (client ưu tiên HTML dù CÓ nhắc markdown — case lỗi phổ biến nhất được cảnh báo, PHẢI test kỹ).
- `Accept: */*` (curl mặc định) → HTML (tie-break theo thứ tự khai báo, §0).
- `Accept: text/markdown;q=0` → HTML (q=0 = từ chối tường minh, không được chọn).
- Bài `format=redirect` → LUÔN redirect ra `redirect_url`, bất kể `Accept` header gửi gì.
- Bài chưa published/slug sai → 404, bất kể `Accept` header.
- Request Markdown KHÔNG làm tăng `view_count`/không tạo bản ghi `post_article_view_events` (verify bằng DB assertion trước/sau).
- Cache: sửa bài → `Accept: text/markdown` lần kế tiếp trả nội dung MỚI ngay, không dính cache cũ.
- H1 trùng lặp, Comparison lệch cột, block mồ côi/loại lạ, ảnh/link tương đối, bài không có content block nào, không có YAML frontmatter — giữ nguyên các case đã liệt kê ở lịch sử spec v1.1/v1.2, áp dụng y hệt cho response Markdown ở kiến trúc mới.

---

## 7. Ngoài phạm vi (v2.0)

- Video/Playlist/Page — chưa làm, nếu làm sau thì tái dùng `NegotiatesMarkdown` (đã đặt ở Support cấp module, không gắn cứng vào Post).
- `resources/{type}` content negotiation (JSON, XML...) — trait chỉ xử lý 2 type hiện tại, mở rộng `$produces` được nhưng chưa có nhu cầu.
- CDN/edge-level cache tuning theo `Vary: Accept` (VD Cloudflare Cache Key theo header) — nằm ngoài tầng ứng dụng, thuộc cấu hình hạ tầng khi triển khai thật, không thuộc code Laravel.
