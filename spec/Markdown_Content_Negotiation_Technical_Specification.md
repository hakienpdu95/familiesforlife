# Bản Markdown cho Bài viết (Markdown Content Negotiation)
**Đặc tả Kỹ thuật Chi tiết — CHƯA triển khai, chờ duyệt trước khi code**

**Phiên bản:** 1.1
**Ngày:** 2026-08-08
**Framework:** Laravel 13 (PHP 8.4) + NWIDART Modules + Lorisleiva Actions
**Module chạm tới:** `Modules/Post` (không tạo module mới — đây là 1 cách hiển thị THÊM của dữ liệu bài viết đã có, giống `toComposerPayload()`/`renderSnapshot()` đã là các "view" khác nhau của cùng `post_content_blocks`)
**Nguồn:** `spec/giadinh.md` — Moz Whiteboard Friday "The SEO's Guide to Agentic Commerce" (Miracle Inameti-Archibong): *"Markdown... is the most efficient way of serving AI your data because it uses fewer tokens."*

> **v1.1 (review nội bộ sau v1.0):** rà lại đưa ra 1 lỗi thật + vài điểm cần siết chặt.
> **Đã sửa:** (1) `renderMarkdown()` thiếu nhánh `ContentBlockType::Testimonial` — bỏ sót vì
> spec này viết TRƯỚC khi khối Testimonial ra đời (đợt 7), giờ đã bổ sung; (2) chuyển chuỗi
> if/elseif ngầm định "không khớp gì thì coi là Product" sang `match()` liệt kê ĐỦ mọi
> `ContentBlockType` + nhánh `default` an toàn (log cảnh báo + trả 1 dòng ghi chú thay vì chuỗi
> rỗng im lặng) — tự bắt được ngay khi có loại block mới mà quên cập nhật file này; (3) thêm
> bước "absolutize" URL ảnh/link tương đối trong khối Text TRƯỚC khi convert — file `.md` không
> có `<base>` HTML để agent tự resolve; (4) thêm phòng thủ lớp 2 (pad/slice) cho
> `comparisonBlockToMarkdown()` dù đã validate khớp số cột lúc ghi (`SyncContentBlocksAction`),
> cùng tinh thần `Video::getWatchUrlAttribute()` tự kiểm tra lại; (5) Product Markdown thêm mô tả
> ngắn (`display_description`, accessor đã có sẵn — dùng chung với `ArticleStructuredDataBuilder`
> ); (6) mở rộng bộ test §7.
> **Đã XEM XÉT nhưng KHÔNG sửa (có căn cứ, ghi lại để khỏi bị nêu lại):** "chữ ký
> `showMarkdown(string $slug, ...)` không nhận `$id` dù route có `{id}`" — ĐÂY KHÔNG PHẢI LỖI:
> Laravel bỏ qua route-param không khai trong signature (không lỗi binding, không throw), và đây
> CHÍNH XÁC là pattern route `.html` hiện có đang chạy production (`PublicArticleController::
> show(string $slug, ...)` — xem comment gốc tại `Modules/Post/routes/web.php` giải thích y hệt
> lý do). `id` trong URL chỉ là hậu tố hiển thị, tra cứu thật LUÔN qua `slug` (đã có
> `unique` constraint toàn hệ thống) — giữ nguyên để nhất quán 2 route `.html`/`.md`, chỉ bổ sung
> docblock dẫn chiếu rõ (§4) để người đọc sau không hiểu nhầm lần nữa. "FAQ/Howto answer/step có
> thể chứa HTML cần convert" — ĐÃ KIỂM CHỨNG LẠI TRỰC TIẾP: 2 field này nhập qua `<textarea>`
> thường (`pbc-faq-answer`/`pbc-howto-step-text`), KHÔNG qua Jodit, và render bằng `{{ }}` tự
> escape ở `faq-block`/`howto-block` — không có HTML thật để convert, giữ nguyên xử lý plain-text.

---

## 0. Quyết định đã chốt

| Chủ đề | Quyết định spec này | Lý do |
|---|---|---|
| **Phạm vi loại nội dung** | v1 CHỈ Post articles (`PublicArticleController::show()`) — không làm cho Video/Playlist/Page | Post là nội dung có giá trị AEO/GEO cao nhất (đã đầu tư JSON-LD/direct_answer/FAQ/Howto/Comparison qua nhiều đợt) và có khối lượng lớn nhất (82+ bài) — Video/Playlist là danh sách ngắn, ít prose, giá trị "phục vụ Markdown" thấp hơn nhiều. Mở rộng sau nếu có nhu cầu thật |
| **Cơ chế: URL suffix `.md` riêng, KHÔNG dùng `Accept` header negotiation trên URL cũ** | Route mới `{slug}-d{id}.md` (đổi hẳn 1 ký tự so với `{slug}-d{id}.html` đã có) — không thêm logic parse `Accept` header trên route HTML hiện tại | (1) Đơn giản hơn: không phải áp `Illuminate\Http\Request::wantsJson()`-style content negotiation (framework không có sẵn `wantsMarkdown()`, phải tự viết + test kỹ tránh vỡ cache/CDN theo `Vary` header). (2) Discoverable hơn: gõ thẳng URL `.md` vào trình duyệt/curl là xem được ngay, không cần set custom header — khớp đúng cách cộng đồng `llmstxt.org` khuyến nghị (liên kết trực tiếp `.md`, không dựa vào content negotiation). (3) Nhiều crawler/agent đơn giản (script `curl`/`fetch` cơ bản) không chủ động gửi `Accept: text/markdown` — URL suffix hoạt động với MỌI client không cần hợp tác gì thêm |
| **KHÔNG coi là "duplicate content" cho SEO** | Không thêm `{slug}-d{id}.md` vào sitemap.xml; giữ `<link rel="canonical">` trên trang `.md` trỏ về đúng URL `.html` gốc | `.md` là định dạng MÁY ĐỌC (như JSON/XML), không phải 1 trang cạnh tranh thứ hạng SERP với bản HTML — cùng nguyên tắc `robots.txt`/`llms.txt` hiện có không bị coi là nội dung trùng lặp |
| **Tái dùng `ArticleContentRenderer`, thêm method `renderMarkdown()`** | Thêm 1 method mới song song `render()`/`toComposerPayload()`/`renderSnapshot()` đã có — mỗi loại `ContentBlockType` có 1 nhánh chuyển sang Markdown riêng | Đúng kiến trúc hiện có của class này: "nhiều cách hiển thị khác nhau của cùng dữ liệu `post_content_blocks`" — không tạo class mới, không lặp lại logic đọc `contentBlocks`/`faqBlock`/`howtoBlock`/`comparisonBlock` đã có sẵn ở `render()` |
| **Thêm dependency `league/html-to-markdown`** | `composer require league/html-to-markdown` — dùng để convert `text_html` (khối Jodit) sang Markdown | Dự án đã có `league/commonmark` (chiều NGƯỢC LẠI: Markdown → HTML, dùng cho việc khác) — không có sẵn package chiều HTML → Markdown. `league/html-to-markdown` là package phổ biến, ổn định, cùng nhà phát hành `league/commonmark` nên tương thích hệ sinh thái |
| **Khối Comparison → bảng Markdown GFM thật, không phải mô tả văn xuôi** | `\| Cột A \| Cột B \|` chuẩn GitHub-Flavored Markdown | Đây là loại content-block MAY MẮN khớp tự nhiên nhất với Markdown — bảng so sánh vốn đã là dữ liệu dạng lưới (`PostComparisonRow::values` đã có sẵn thứ tự khớp cột), không cần "dịch" gì thêm |
| **Không cache bản Markdown** | Sinh on-the-fly mỗi request, giống `render()` (HTML) hiện tại không cache | Cùng khối lượng tính toán với `render()` (lặp qua content blocks đã eager-load, không có query nặng/gọi AI) — thêm cache là tối ưu sớm không cần thiết, đúng nguyên tắc đã áp dụng nhiều nơi trong dự án ("không thêm engineering khi chưa có bằng chứng cần") |
| **Throttle route mới** | `throttle:60,1` — cùng mức đã dùng cho `PensionCalculator` (đọc dữ liệu công khai, không nhận input cá nhân) | Route mới, không có tiền lệ traffic thật — chặn scrape thô sơ mà không ảnh hưởng crawler/agent hợp lệ (60 req/phút đủ rộng cho use-case đọc từng bài) |
| **Có `<link rel="alternate" type="text/markdown">` trên trang HTML** | Thêm vào `<head>` của `article.blade.php`, trỏ tới URL `.md` tương ứng | Cách chuẩn HTML để khai báo "phiên bản khác của cùng nội dung" (giống RSS `<link rel="alternate" type="application/rss+xml">`) — giúp agent/crawler tự phát hiện bản Markdown mà không cần đoán URL pattern |
| **`llms.txt` không đổi cấu trúc, chỉ thêm 1 dòng ghi chú** | Thêm 1 dòng cuối `llms.blade.php`: "Mọi bài viết có bản Markdown tại `{url}.md`" | Xác nhận rõ khả năng này cho agent đọc `llms.txt` trước, không bắt agent tự đoán |
| **Không áp dụng cho bài `format=redirect`** | Route `.md` trả 404 nếu `$article->isRedirect()` | Bài redirect không có nội dung riêng (redirect thẳng ra `redirect_url` ở bản HTML) — không có gì để chuyển thành Markdown |

---

## 1. Giới thiệu & Mục tiêu

Bài viết công khai hiện chỉ có 1 dạng biểu diễn: HTML đầy đủ (kèm layout/nav/footer/script). Theo Moz WBF "The SEO's Guide to Agentic Commerce": AI agent xử lý dữ liệu trong 1 "context window" giới hạn, và phục vụ HTML cho agent tốn token vô ích để agent tự "dọn" thẻ/style/script trước khi hiểu được nội dung thật — Markdown dùng ít token hơn nhiều cho cùng lượng thông tin.

Giải pháp: thêm 1 URL song song `{slug}-d{id}.md` trả về bản Markdown THUẦN của bài viết (không layout, không nav/footer/script) — dùng lại chính `post_content_blocks` đã có, không cần dữ liệu mới.

---

## 2. Route

```php
// Modules/Post/routes/web.php — thêm route mới, đặt CẠNH route .html hiện có
Route::get('{slug}-d{id}.md', [PublicArticleController::class, 'showMarkdown'])
    ->where(['slug' => '[a-z0-9\-]+', 'id' => '[0-9]+'])
    ->middleware('throttle:60,1')
    ->name('post.public.article.markdown');
```

---

## 3. `ArticleContentRenderer::renderMarkdown()`

```php
/**
 * Bản Markdown thuần — dùng cho route `.md` (§0 Markdown_Content_Negotiation_Technical_
 * Specification.md). Cùng nguồn dữ liệu với render() (HTML), khác cách chuyển đổi từng loại
 * ContentBlockType. Header (tiêu đề/direct_answer/nguồn/ngày cập nhật) do Controller ghép
 * riêng — method này CHỈ render phần BODY từ content blocks.
 */
public function renderMarkdown(PostArticleTranslation $translation): string
{
    $blocks = $translation->contentBlocks()
        ->with([
            'productBlock.items.product' => fn ($q) => $q->publicEmbed(),
            'productBlock.items.buttons', 'faqBlock.items', 'howtoBlock.steps',
            'comparisonBlock.columns', 'comparisonBlock.rows',
        ])
        ->get();

    $converter = new \League\HTMLToMarkdown\HtmlConverter(['strip_tags' => true]);

    // match() liệt kê ĐỦ mọi ContentBlockType (v1.1 — sửa sau review: bản v1.0 dùng chuỗi
    // if/elseif kết thúc bằng 1 fallback ngầm định "không khớp gì thì coi là Product", khiến 1
    // loại block mới thêm sau (quên cập nhật file này) render RỖNG hoặc SAI thành Product mà
    // không ai biết. match() không có nhánh nào khớp sẽ rơi vào `default` — an toàn, LOGGING rõ
    // ràng, không vỡ cả trang vì 1 loại block lạ.
    return $blocks->map(function ($block) use ($converter) {
        $markdown = match ($block->type) {
            ContentBlockType::Text => trim($converter->convert($this->absolutizeUrls((string) $block->text_html))),
            ContentBlockType::Faq => $block->faqBlock ? $this->faqBlockToMarkdown($block->faqBlock) : null,
            ContentBlockType::Citation => $this->citationBlockToMarkdown($block),
            ContentBlockType::Howto => $block->howtoBlock ? $this->howtoBlockToMarkdown($block->howtoBlock) : null,
            ContentBlockType::Comparison => $block->comparisonBlock ? $this->comparisonBlockToMarkdown($block->comparisonBlock) : null,
            ContentBlockType::Testimonial => $this->testimonialBlockToMarkdown($block),
            ContentBlockType::Product => $block->productBlock ? $this->productBlockToMarkdown($block->productBlock) : null,
            default => null,
        };

        if ($markdown !== null) {
            return $markdown;
        }

        // Quan hệ mồ côi (faqBlock/howtoBlock/comparisonBlock đã bị xoá) HOẶC loại block mới
        // chưa được xử lý ở match() trên — trả 1 dòng ghi chú THẤY ĐƯỢC thay vì chuỗi rỗng im
        // lặng (agent đọc .md biết rõ có phần nội dung không hiển thị được, không tưởng bài thiếu
        // hẳn 1 đoạn do lỗi convert). Log cảnh báo để dev biết cần bổ sung nếu là loại block mới.
        \Illuminate\Support\Facades\Log::warning('post.renderMarkdown: block không xử lý được', ['type' => $block->type->value, 'block_id' => $block->id]);

        return "_({$block->type->label()} — nội dung không khả dụng)_";
    })->filter()->implode("\n\n");
}

/**
 * Agent đọc file .md KHÔNG có `<base>` HTML để tự resolve URL tương đối (v1.1 — bổ sung sau
 * review, bản v1.0 thiếu bước này). Chỉ xử lý URL bắt đầu bằng "/" (root-relative — trường hợp
 * phổ biến nhất từ Media Library) — không xử lý protocol-relative "//host/..." hay relative
 * không có "/" đầu, chấp nhận được ở v1 vì đó không phải dạng URL Jodit/Media Library sinh ra.
 */
private function absolutizeUrls(string $html): string
{
    return preg_replace_callback(
        '/(src|href)="(\/[^"]*)"/i',
        fn ($m) => $m[1].'="'.url($m[2]).'"',
        $html
    );
}

private function faqBlockToMarkdown(PostFaqBlock $block): string
{
    $heading = $block->heading ? "## {$block->heading}\n\n" : '';

    return $heading.$block->items->map(
        fn ($item) => "**{$item->question}**\n\n{$item->answer}"
    )->implode("\n\n");
}

private function howtoBlockToMarkdown(PostHowtoBlock $block): string
{
    $heading = $block->name ? "## {$block->name}\n\n" : '';
    $desc = $block->description ? "{$block->description}\n\n" : '';
    $steps = $block->steps->map(
        fn ($step, $i) => ($i + 1).". **{$step->name}** — {$step->text}"
    )->implode("\n");

    return $heading.$desc.$steps;
}

/** Bảng GFM thật — khớp tự nhiên nhất với dữ liệu columns/rows đã có sẵn thứ tự (§0). */
private function comparisonBlockToMarkdown(PostComparisonBlock $block): string
{
    $heading = $block->name ? "## {$block->name}\n\n" : '';
    $desc = $block->description ? "{$block->description}\n\n" : '';
    $columnCount = $block->columns->count();

    $headerRow = '| | '.$block->columns->map(fn ($c) => $c->label)->implode(' | ').' |';
    $separator = '|---'.str_repeat('|---', $columnCount).'|';
    $bodyRows = $block->rows->map(function ($row) use ($columnCount) {
        // Phòng thủ lớp 2 (v1.1 — bổ sung sau review) — dữ liệu ĐÃ được validate khớp số cột
        // lúc ghi (SyncContentBlocksAction::validateComparisonBlocks()), nhưng vẫn tự vá nếu có
        // sai lệch do sửa tay/migration lỗi (cùng tinh thần Video::getWatchUrlAttribute() tự
        // kiểm tra lại host dù đã validate khi lưu) — tránh bảng Markdown bị lệch cột/vỡ cấu trúc.
        $values = array_pad(array_slice($row->values, 0, $columnCount), $columnCount, '');

        return "| **{$row->label}** | ".implode(' | ', $values).' |';
    })->implode("\n");

    return "{$heading}{$desc}{$headerRow}\n{$separator}\n{$bodyRows}";
}

/** Testimonial không có bảng con — field trực tiếp trên PostContentBlock (cùng Citation). */
private function testimonialBlockToMarkdown(PostContentBlock $block): string
{
    $person = $block->testimonial_person_name;
    $roleParts = array_filter([$block->testimonial_person_title, $block->testimonial_company_name]);
    if ($roleParts) {
        $person .= ' — '.implode(', ', $roleParts);
    }

    $footer = "> — {$person}";
    if ($block->testimonial_result_metric) {
        $footer .= " ({$block->testimonial_result_metric})";
    }

    return "> \"{$block->testimonial_quote}\"\n{$footer}";
}

private function citationBlockToMarkdown(PostContentBlock $block): string
{
    $quote = "> {$block->citation_text}";
    $source = $block->citation_source_url
        ? "> — [{$block->citation_source_name}]({$block->citation_source_url})"
        : "> — {$block->citation_source_name}";

    return "{$quote}\n{$source}";
}

private function productBlockToMarkdown(PostProductBlock $block): string
{
    $heading = $block->heading ? "## {$block->heading}\n\n" : '';

    return $heading.$block->items->map(function ($item) {
        $name = $item->display_title;
        $price = $item->display_price ? " ({$item->display_price})" : '';
        // v1.1 — thêm mô tả ngắn (bổ sung sau review, bản v1.0 chỉ có tên+giá, quá rút gọn để
        // agent hiểu sản phẩm là gì). Dùng chung accessor `display_description` với
        // ArticleStructuredDataBuilder::buildProducts() — không cần thêm field/logic mới.
        $desc = $item->display_description ? "\n  ".\Illuminate\Support\Str::limit($item->display_description, 150) : '';

        return "- **{$name}**{$price}{$desc}";
    })->implode("\n");
}
```

> **Chưa xử lý ở v1.1 — cần xác nhận khi code:** link CTA thật của mỗi item (`route('post.cta.redirect', $button)` — cùng URL click-tracking đang dùng ở trang HTML) CHƯA được thêm vào Markdown vì cần đối chiếu lại đúng cách `product-block/*.blade.php` đang resolve `href` cho từng `url_type` (đặc biệt `use_product_link` — không có `url` tĩnh, phải resolve động) trước khi viết — tránh đoán sai accessor. Thêm sau nếu cần, không chặn v1.1.

> Các private method mới cần import thêm `PostFaqBlock`/`PostHowtoBlock`/`PostComparisonBlock`/`PostProductBlock`/`PostContentBlock` (đã import 1 phần ở class hiện tại, bổ sung nốt).

---

## 4. `PublicArticleController::showMarkdown()`

```php
/**
 * Chữ ký CHỈ nhận `$slug`, KHÔNG nhận `$id` dù route `{slug}-d{id}.md` có khai `{id}` — ĐÚNG Ý,
 * không phải thiếu sót (đã bị nêu nhầm là lỗi ở review v1.0, xem changelog đầu file): Laravel bỏ
 * qua route-param không có trong signature (không lỗi binding), và đây CHÍNH XÁC là pattern route
 * `.html` hiện có đang chạy (`PublicArticleController::show(string $slug, ...)`) — `id` trong URL
 * chỉ là hậu tố hiển thị, tra cứu thật luôn qua `slug` (đã có unique constraint toàn hệ thống).
 * Giữ nhất quán 2 route thay vì thêm `$id` không dùng tới.
 */
public function showMarkdown(string $slug, ArticleContentRenderer $renderer): \Illuminate\Http\Response
{
    $translation = PostArticleTranslation::published()
        ->where('locale', config('post.default_locale'))
        ->where('slug', $slug)
        ->with(['article'])
        ->first();

    abort_unless($translation, 404);

    $article = $translation->article;
    // Bài redirect không có nội dung riêng — không có gì để chuyển Markdown (§0).
    abort_if($article?->isRedirect(), 404);

    $canonicalUrl = route('post.public.article', ['slug' => $translation->slug, 'id' => $translation->id]);
    $description = $translation->seo_description ?: $translation->direct_answer ?: $translation->excerpt;

    $header = "# {$translation->title}\n\n";
    if ($description) {
        $header .= "> {$description}\n\n";
    }
    $header .= "Nguồn: {$canonicalUrl}\n";
    if ($translation->updated_at) {
        $header .= 'Cập nhật: ' . $translation->updated_at->format('d/m/Y') . "\n";
    }
    $header .= "\n---\n\n";

    $markdown = $header . $renderer->renderMarkdown($translation);

    return response($markdown, 200)
        ->header('Content-Type', 'text/markdown; charset=UTF-8');
}
```

---

## 5. `article.blade.php` — thêm `<link rel="alternate">`

```blade
@push('meta')
{{-- ... các meta hiện có ... --}}
<link rel="alternate" type="text/markdown" href="{{ route('post.public.article.markdown', ['slug' => $translation->slug, 'id' => $translation->id]) }}" title="Bản Markdown">
@endpush
```

---

## 6. `llms.blade.php` — thêm 1 dòng ghi chú (không đổi cấu trúc mục lục hiện có)

```blade
## Bản Markdown cho AI agent

Mọi bài viết có bản Markdown thuần — lấy URL bài viết, đổi đúng phần đuôi `.html` ở cuối thành
`.md`, giữ nguyên phần còn lại (VD: `/bai-viet-d123.html` → `/bai-viet-d123.md`).
```

---

## 7. Kiểm thử bắt buộc

- Route `.md` trả đúng `Content-Type: text/markdown; charset=UTF-8`, không kèm layout/nav/footer/script.
- Bài `format=redirect` → route `.md` trả 404 (không redirect ra `redirect_url` như bản HTML).
- Bài chưa published/slug sai → 404 (đồng nhất với route `.html`).
- **`id` trong URL không khớp `translation->id` thật** (VD sửa tay thành số khác) → vẫn trả đúng 200 theo `slug` (khoá lại hành vi CHỦ ĐÍCH đã ghi ở §4, tránh ai đó "sửa" thành lỗi thật ở lần sau).
- Khối Comparison → bảng Markdown parse đúng bằng 1 thư viện Markdown chuẩn (test bằng cách feed lại qua `league/commonmark` xem có sinh đúng `<table>` không — round-trip test); thử thêm 1 case cố tình lệch số `values` so với số cột (sửa thẳng DB) → xác nhận `array_pad`/`array_slice` không vỡ bảng.
- Khối Text có HTML phức tạp (bold/italic/link/list lồng nhau, **list lồng nhau nhiều cấp, `<table>` chèn trong Text, `<img>`/`<a>` có `title`, HTML entity**) → convert Markdown không mất thông tin quan trọng — test với **5-10 mẫu `text_html` THẬT lấy từ DB** (không phải mẫu tự viết đơn giản), không chỉ "vài mẫu" chung chung.
- **Ảnh/link tương đối (`src="/uploads/..."`) trong khối Text** → sau convert phải thành URL tuyệt đối (`absolutizeUrls()`), test cả trường hợp URL đã tuyệt đối sẵn (không bị đổi thành sai).
- **1 bài chỉ có block loại chưa xử lý (hoặc `faqBlock`/`howtoBlock`/`comparisonBlock` bị xoá mồ côi)** → trả dòng ghi chú thấy được (`_(...)_`), có log warning, KHÔNG trả chuỗi rỗng im lặng, KHÔNG lỗi 500.
- **Bài nhiều loại block xen kẽ đúng thứ tự `sort_order`** (Text → Comparison → Testimonial → Faq...) → Markdown giữ ĐÚNG thứ tự đó.
- **Bài không có content block nào** (mới tạo, chưa soạn) → route `.md` vẫn trả 200, chỉ có phần header (title/mô tả/nguồn), không lỗi.
- `<link rel="alternate">` xuất hiện đúng trên trang HTML, trỏ đúng URL `.md` của CHÍNH bài đó.
- Throttle 60/phút hoạt động (request thứ 61 trong 1 phút → 429); sau khi hết cửa sổ 1 phút, request tiếp theo phải thành công trở lại (test recovery, không chỉ test lúc bị chặn).

---

## 8. Ngoài phạm vi (v1)

- Video/Playlist/Page — chưa làm, mở rộng sau nếu có nhu cầu thật.
- `Accept` header content negotiation trên URL `.html` gốc — đã cân nhắc, chọn URL suffix riêng (§0).
- Cache bản Markdown — chưa cần (§0).
- Markdown cho trang danh mục/trang chủ (danh sách nhiều bài) — v1 chỉ có trang chi tiết 1 bài.
