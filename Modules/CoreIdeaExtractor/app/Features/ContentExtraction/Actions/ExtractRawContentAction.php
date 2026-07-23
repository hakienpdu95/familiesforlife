<?php

namespace Modules\CoreIdeaExtractor\Features\ContentExtraction\Actions;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\CoreIdeaExtractor\Features\ContentExtraction\Data\HeadingData;

/**
 * Layer 1 — spec/CoreIdeaExtractor.md §5.2/§5.3. Parse HTML bằng DOMDocument/DOMXPath built-in
 * (repo không có package parse HTML chuyên dụng) — cùng convention load an toàn đã dùng ở
 * Modules/Post/app/Support/ArticleContentRenderer.php::sanitizeTextHtml() (libxml_use_internal_errors
 * + LIBXML_NOERROR|LIBXML_NOWARNING), áp dụng cho parse HTML ĐẦY ĐỦ (không phải fragment).
 */
class ExtractRawContentAction
{
    use AsAction;

    /** class/id chứa 1 trong các từ khoá này bị coi là noise (menu/sidebar/quảng cáo...). */
    private const NOISE_KEYWORDS = [
        'menu', 'nav', 'sidebar', 'comment', 'share', 'related',
        'advertisement', 'ads', 'breadcrumb', 'pagination', 'cookie', 'popup', 'modal', 'footer',
    ];

    /** Heading text khớp 1 trong các cụm này (sau khi lowercase) coi là trang trí, không "có ý nghĩa" (§6.1.4 v1.3). */
    private const DECORATIVE_HEADING_PHRASES = [
        'xem thêm', 'chia sẻ', 'bình luận', 'liên quan', 'share', 'related', 'comments', 'đọc thêm',
        'từ khoá', 'từ khóa', 'tags', 'xem nhiều', 'cùng chuyên mục', 'tin liên quan',
    ];

    /**
     * Dò substring thô trên @id/@class (KHÔNG word-boundary) — cố ý rộng hơn ví dụ literal của
     * spec §5.3 ("content, post, entry, body, article-body") vì thực tế CMS đặt tên rất đa dạng
     * (VD Wikipedia dùng #mw-content-text/#bodyContent, không khớp bất kỳ literal nào trong
     * spec) — đánh đổi: có thể khớp nhầm 1 vài container không liên quan, nhưng đã lọc bớt qua
     * stripNoise() trước đó (menu/nav/sidebar/footer...) nên rủi ro chấp nhận được.
     */
    private const MAIN_CONTENT_SELECTORS = [
        "//*[contains(@id, 'content')]", "//*[contains(@class, 'content')]",
        "//*[contains(@id, 'post')]", "//*[contains(@class, 'post')]",
        "//*[contains(@id, 'entry')]", "//*[contains(@class, 'entry')]",
        "//*[contains(@id, 'article')]", "//*[contains(@class, 'article')]",
        "//*[contains(@id, 'body')]", "//*[contains(@class, 'body')]",
        '//main',
    ];

    private const PAYWALL_KEYWORDS = ['paywall', 'premium-content', 'subscriber-only', 'đăng nhập để đọc tiếp', 'subscribe to continue'];

    /**
     * Thẻ block-level cần chèn ranh giới đoạn khi nối text — DOMNode::textContent tự nó KHÔNG
     * chèn khoảng trắng/newline giữa các thẻ (chỉ nối chuỗi text node liền nhau), nên với HTML
     * đã minify (không có whitespace thật giữa các tag trong response server trả về), 2 đoạn văn
     * liên tiếp bị dính chữ liền nhau không có ranh giới (đã gặp thật, VD ".significant.”Why
     * diastasis..." khi test thật với site minify HTML).
     */
    private const BLOCK_TAGS = [
        'p', 'div', 'section', 'article', 'header', 'footer',
        'h1', 'h2', 'h3', 'h4', 'h5', 'h6',
        'li', 'ul', 'ol', 'blockquote', 'figure', 'figcaption',
        'table', 'tr', 'td', 'th', 'pre',
    ];

    /**
     * @param string|null $mainContentSelector Selector đơn giản (id/class, kiểu ".detail-content",
     * "#main-content", có thể kèm tag như "div.detail-content") do người dùng chỉ định để khoanh
     * vùng main_content thay cho thuật toán tự động resolveContentRoot(). Null/rỗng → tự động.
     * @return array{title:?string, meta_description:?string, keywords:string[], headings:HeadingData[], main_content:string, publish_date:?string, author:?string, language:string, word_count:int, meaningful_heading_count:int, paywall_suspected:bool, custom_selector_matched:?bool}
     */
    public function handle(string $html, ?string $mainContentSelector = null): array
    {
        $dom = new \DOMDocument('1.0', 'UTF-8');
        libxml_use_internal_errors(true);
        // libxml's HTML parser thường KHÔNG nhận diện được `<meta charset="utf-8">` kiểu HTML5
        // (nhất là khi charset không phải attribute đầu tiên của thẻ meta, VD trang SSR/Nuxt.js:
        // `<meta data-n-head="ssr" charset="utf-8">`) và mặc định coi input là ISO-8859-1, làm vỡ
        // MỌI ký tự tiếng Việt có dấu dù $html (từ FetchArticleHtmlAction) đã đúng là UTF-8 —
        // (VD gặp thật: "Lịch ăn dặm" bị đọc thành "Lá»ch Än dáº·m"). Prefix pseudo-declaration
        // `<?xml encoding="UTF-8">` ép libxml parse đúng theo UTF-8 mà KHÔNG chèn node thật vào
        // DOM (libxml có xử lý đặc biệt cho khai báo này khi dùng với loadHTML).
        $dom->loadHTML('<?xml encoding="UTF-8">'.$html, LIBXML_NOERROR | LIBXML_NOWARNING);
        libxml_clear_errors();

        $xpath = new \DOMXPath($dom);

        $language        = $this->extractLanguage($xpath);
        $metaDescription = $this->extractMetaDescription($xpath);
        $keywords        = $this->extractKeywords($xpath);
        $publishDate     = $this->extractPublishDate($xpath, $html);
        $author          = $this->extractAuthor($xpath, $html);
        $paywallSuspected = $this->looksLikePaywalled($xpath);

        // Title lấy TRƯỚC khi xoá noise (nằm trong <head>, không bị ảnh hưởng), nhưng fallback
        // h1 phải lấy SAU khi xoá noise (tránh h1 trang trí trong header/nav).
        $rawTitle = $this->extractTitleFromHead($xpath);

        $this->stripNoise($xpath);

        $title = $rawTitle ?: $this->firstHeadingText($xpath, 'h1');

        // Nếu người dùng chỉ định selector riêng (id/class), ưu tiên dùng khối đó làm root thay
        // vì thuật toán tự động — cho phép người dùng "sửa tay" khi thuật toán chọn sai container
        // (thường gặp với site có bố cục lạ/hiếm). Không khớp được (selector sai/không tồn tại
        // trên trang) → coi như không chỉ định, rơi về thuật toán tự động như cũ, không throw lỗi.
        $customSelectorMatched = null;
        $contentRoot = null;

        if ($mainContentSelector !== null && trim($mainContentSelector) !== '') {
            $contentRoot = $this->resolveContentRootFromSelector($xpath, $mainContentSelector);
            $customSelectorMatched = $contentRoot !== null;
        }

        // Xác định 1 node GỐC duy nhất cho nội dung chính, rồi soi headings TRONG PHẠM VI node
        // đó (không quét cả document) — nhiều site (VD báo Việt Nam) dùng <article> cho MỌI thẻ
        // "story card" (bài liên quan/xem nhiều), không chỉ bài chính; quét headings toàn trang
        // sẽ lẫn cả tiêu đề các bài KHÁC không liên quan (đã gặp thật: "Xem nhiều"/"Cùng chuyên
        // mục" kèm tiêu đề 10 bài linh tinh). Cùng root cho main_content luôn đảm bảo 2 field
        // nhất quán với nhau (headings chắc chắn nằm trong main_content, không lệch phạm vi).
        if ($contentRoot === null) {
            $contentRoot = $this->resolveContentRoot($xpath);
        }

        $mainContent = $contentRoot ? $this->cleanText($this->extractBlockText($contentRoot)) : '';
        $headings    = $this->extractHeadings($xpath, $contentRoot);
        $wordCount   = $this->countWords($mainContent, $language);

        return [
            'title'                    => $title !== '' ? $title : null,
            'meta_description'        => $metaDescription,
            'keywords'                 => $keywords,
            'headings'                 => $headings,
            'main_content'             => $mainContent,
            'publish_date'             => $publishDate,
            'author'                   => $author,
            'language'                 => $language,
            'word_count'               => $wordCount,
            'meaningful_heading_count' => count($headings),
            'paywall_suspected'        => $paywallSuspected,
            'custom_selector_matched'  => $customSelectorMatched,
        ];
    }

    /**
     * meta[name="keywords"] chuẩn HTML là 1 chuỗi các từ khoá phân tách bởi dấu phẩy — tách ra
     * mảng string, bỏ khoảng trắng thừa và phần tử rỗng (VD content="a, b,, c" → ["a","b","c"]).
     *
     * @return string[]
     */
    private function extractKeywords(\DOMXPath $xpath): array
    {
        $content = $this->metaContent($xpath, "//meta[@name='keywords']");

        if ($content === null) {
            return [];
        }

        $parts = array_map('trim', explode(',', $content));

        return array_values(array_filter($parts, static fn (string $p) => $p !== ''));
    }

    /**
     * Chuyển selector đơn giản do người dùng nhập (id/class, có thể kèm tag) thành XPath rồi tìm
     * node đầu tiên khớp. Chỉ hỗ trợ cú pháp CSS selector cơ bản nhất — ĐÚNG PHẠM VI yêu cầu
     * (chọn khối theo id/class, kiểu "class=\"detail-content\""), không phải 1 CSS-selector-engine
     * đầy đủ (không hỗ trợ tổ hợp combinator/pseudo-class — nếu cần phức tạp hơn, thuật toán tự
     * động resolveContentRoot() vẫn là fallback an toàn).
     *
     * Cú pháp hỗ trợ (có thể liệt kê nhiều, phân tách bởi dấu phẩy, thử lần lượt theo thứ tự,
     * dùng khối đầu tiên khớp): ".class", "#id", "tag.class", "tag#id", hoặc chỉ "tag".
     */
    private function resolveContentRootFromSelector(\DOMXPath $xpath, string $selector): ?\DOMNode
    {
        foreach (explode(',', $selector) as $part) {
            $query = $this->simpleSelectorToXPath(trim($part));

            if ($query === null) {
                continue;
            }

            $node = $xpath->query($query)?->item(0);

            if ($node instanceof \DOMNode) {
                return $node;
            }
        }

        return null;
    }

    private function simpleSelectorToXPath(string $part): ?string
    {
        if ($part === '') {
            return null;
        }

        if (! preg_match('/^([a-zA-Z][a-zA-Z0-9]*)?([.#])([a-zA-Z0-9_-]+)$/', $part, $m)) {
            // Không khớp cú pháp id/class hỗ trợ — coi selector này là tên tag thuần (VD "article").
            return preg_match('/^[a-zA-Z][a-zA-Z0-9]*$/', $part) ? "//{$part}" : null;
        }

        [, $tag, $marker, $name] = $m;
        $tag = $tag !== '' ? $tag : '*';

        if ($marker === '#') {
            return "//{$tag}[@id='{$name}']";
        }

        return "//{$tag}[contains(concat(' ', normalize-space(@class), ' '), ' {$name} ')]";
    }

    private function extractTitleFromHead(\DOMXPath $xpath): string
    {
        $titleNode = $xpath->query('//title')->item(0);
        if ($titleNode && trim($titleNode->textContent) !== '') {
            return trim($titleNode->textContent);
        }

        $ogTitle = $this->metaContent($xpath, "//meta[@property='og:title']");
        if ($ogTitle) {
            return $ogTitle;
        }

        return '';
    }

    private function firstHeadingText(\DOMXPath $xpath, string $tag): string
    {
        $node = $xpath->query("//{$tag}")->item(0);

        return $node ? trim($node->textContent) : '';
    }

    private function extractMetaDescription(\DOMXPath $xpath): ?string
    {
        return $this->metaContent($xpath, "//meta[@name='description']")
            ?? $this->metaContent($xpath, "//meta[@property='og:description']");
    }

    private function metaContent(\DOMXPath $xpath, string $query): ?string
    {
        $node = $xpath->query($query)->item(0);

        if (! $node instanceof \DOMElement) {
            return null;
        }

        $content = trim($node->getAttribute('content'));

        return $content !== '' ? $content : null;
    }

    private function extractLanguage(\DOMXPath $xpath): string
    {
        $html = $xpath->query('//html')->item(0);

        if (! $html instanceof \DOMElement) {
            return 'unknown';
        }

        $lang = trim($html->getAttribute('lang'));

        if ($lang === '') {
            return 'unknown';
        }

        return strtolower(explode('-', $lang)[0]);
    }

    /**
     * Xoá node script/style/nav/footer/aside + node có class/id khớp NOISE_KEYWORDS.
     *
     * KHÔNG xoá `<header>` blanket — HTML5 `<header>` cũng hợp lệ dùng làm header CỦA 1
     * section/article (VD Wikipedia bọc chính <h1> tiêu đề bài trong <header>), xoá blanket sẽ
     * mất luôn tiêu đề bài viết (đã gặp thật khi test với trang Wikipedia thật — main_content/
     * headings ra rỗng vì h1 title nằm trong <header>).
     *
     * KHÔNG áp NOISE_KEYWORDS lên <html>/<body> — nhiều site gắn rất nhiều class feature-flag/
     * utility lên 2 tag này (VD Wikipedia: class="... vector-feature-navigation-update-disabled
     * ... vector-feature-language-in-main-menu-disabled ...") — bare-substring 'nav'/'menu' khớp
     * NHẦM các class không liên quan gì đến menu/nav thật, và vì <html>/<body> là node GỐC, xoá
     * nhầm nó sẽ xoá theo CẢ TOÀN BỘ document (đã gặp thật: 100% nội dung biến mất). Chỉ xoá
     * DESCENDANT của <body>, không bao giờ xoá chính <html>/<body>.
     */
    /**
     * Ngưỡng an toàn khi xoá noise theo NOISE_KEYWORDS (substring match trên class/id, không
     * phải nghĩa ngữ nghĩa) — nếu node khớp lại chiếm từ tỉ lệ này trở lên tổng chữ của <body>,
     * KHÔNG xoá (xem lý do trong docblock stripNoise()).
     */
    private const NOISE_MAX_BODY_TEXT_RATIO = 0.4;

    /**
     * Xoá node script/style/nav/footer/aside + node có class/id khớp NOISE_KEYWORDS.
     *
     * Với NOISE_KEYWORDS: chỉ xoá khi node đó chiếm PHẦN NHỎ tổng chữ của <body> — đã gặp thật
     * với vnexpress.net: toàn bộ khối nội dung chính (<main><article class="fck_detail">...)
     * nằm trong wrapper layout <div class="sidebar-1"> (tên class do site tự đặt cho cột bố cục,
     * KHÔNG phải sidebar/menu phụ thật) — class match substring "sidebar" (sau khi translate '-'
     * thành khoảng trắng: "sidebar-1" → "sidebar 1") nên bị NOISE_KEYWORDS bắt nhầm và xoá mất
     * toàn bộ nội dung bài viết thật (3857/4365 ký tự = 88% tổng <body>). Sidebar/nav/footer THẬT
     * hầu như luôn chỉ chiếm phần nhỏ trang, nên dùng tỉ lệ làm van an toàn: node càng LỚN so với
     * tổng trang càng khó là noise thật, bất kể tên class trùng khớp thế nào.
     */
    private function stripNoise(\DOMXPath $xpath): void
    {
        foreach (['script', 'style', 'nav', 'footer', 'aside'] as $tag) {
            foreach (iterator_to_array($xpath->query("//{$tag}")) as $node) {
                $node->parentNode?->removeChild($node);
            }
        }

        $bodyNode = $xpath->query('//body')->item(0);
        $totalLen = $bodyNode ? mb_strlen($this->cleanText($bodyNode->textContent)) : 0;
        $maxNoiseLen = (int) round($totalLen * self::NOISE_MAX_BODY_TEXT_RATIO);

        $conditions = array_map(
            fn (string $kw) => "contains(translate(concat(' ', normalize-space(@class), ' ', normalize-space(@id), ' '), 'ABCDEFGHIJKLMNOPQRSTUVWXYZ-_', 'abcdefghijklmnopqrstuvwxyz  '), ' {$kw} ')",
            self::NOISE_KEYWORDS
        );

        // Loại chính h1/h2/h3 khỏi diện bị xoá theo NOISE_KEYWORDS — đã gặp thật với
        // orami.co.id: heading thật <h2 id="ide-menu-makanan-bayi-1-tahun"> (id tự sinh từ chính
        // text heading, kiểu slug cho anchor/TOC) chứa substring "menu" ("ide-MENU-makanan...")
        // nên bị bắt nhầm là menu điều hướng và xoá mất cả câu heading. id/class kiểu slug-từ-
        // chính-nội-dung rất dễ trùng ngẫu nhiên với NOISE_KEYWORDS (menu, modal, cookie... đều là
        // từ thường gặp trong văn bản thường, không riêng gì UI điều hướng) — trong khi noise
        // THẬT (nav/menu/popup...) hầu như luôn là 1 khối container (div/ul), không phải bản thân
        // 1 thẻ heading. Container bọc ngoài heading (nếu đúng là noise thật) vẫn bị xoá bình
        // thường qua chính node cha của nó.
        $query = '//*[not(self::html) and not(self::body) and not(self::h1) and not(self::h2) and not(self::h3) and ('.implode(' or ', $conditions).')]';

        foreach (iterator_to_array($xpath->query($query)) as $node) {
            if ($totalLen > 0 && mb_strlen($this->cleanText($node->textContent)) > $maxNoiseLen) {
                continue;
            }

            $node->parentNode?->removeChild($node);
        }
    }

    /** @return HeadingData[] */
    private function extractHeadings(\DOMXPath $xpath, ?\DOMNode $contextNode): array
    {
        $query    = './/h1 | .//h2 | .//h3';
        $nodes    = $contextNode ? $xpath->query($query, $contextNode) : $xpath->query('//h1 | //h2 | //h3');
        $headings = [];

        foreach ($nodes as $node) {
            $text = trim(preg_replace('/\s+/', ' ', $node->textContent) ?? '');

            if ($text === '' || $this->isDecorativeHeading($text)) {
                continue;
            }

            $headings[] = new HeadingData(level: (int) substr($node->nodeName, 1), text: $text);
        }

        return $headings;
    }

    private function isDecorativeHeading(string $text): bool
    {
        if (mb_strlen($text) <= 2) {
            return true;
        }

        $lower = mb_strtolower($text);

        foreach (self::DECORATIVE_HEADING_PHRASES as $phrase) {
            if ($lower === $phrase || mb_strlen($text) <= mb_strlen($phrase) + 3 && str_contains($lower, $phrase)) {
                return true;
            }
        }

        return false;
    }

    /** Ứng viên match quá vụn vặt (VD 1 <div class="post-count">Bình luận</div>) không đáng xét. */
    private const MIN_CANDIDATE_CHARS = 100;

    /**
     * Nếu 1 ứng viên con-cháu đã "gói gọn" từ tỉ lệ này trở lên độ dài của ứng viên cha, coi cha
     * là wrapper thừa (loại cha, giữ con cụ thể hơn). Nếu con chỉ là 1 mảnh nhỏ bên trong cha
     * (VD embed/widget lồng trong khối nội dung to hơn nhiều), KHÔNG loại cha vì con không phản
     * ánh đủ nội dung — xem lý do chi tiết ở docblock resolveContentRoot().
     */
    private const ANCESTOR_REDUNDANCY_RATIO = 0.6;

    /**
     * Chọn 1 node GỐC duy nhất cho main_content + headings — dùng chung, không xử lý riêng lẻ
     * (tránh 2 field lệch phạm vi nhau).
     *
     * Thuật toán: gộp TẤT CẢ ứng viên (mọi <article> + mọi node khớp MAIN_CONTENT_SELECTORS) vào
     * 1 pool, loại các ứng viên là "wrapper thừa" (xem ANCESTOR_REDUNDANCY_RATIO), rồi lấy ứng
     * viên còn lại dài nhất.
     *
     * KHÔNG được chọn theo kiểu "ứng viên dài nhất trong TOÀN pool" (kể cả không short-circuit ở
     * <article>) — đã gặp thật với nhandan.vn: <div class="site-body"> (bọc CẢ bài viết LẪN
     * widget "Có thể bạn quan tâm" gồm hàng chục tiêu đề bài khác) luôn dài hơn <div
     * class="article__body"> (nội dung bài viết thật) — chọn theo độ dài thô sẽ lấy nhầm
     * site-body, kéo theo hàng chục heading của các bài không liên quan bị lẫn vào.
     *
     * NHƯNG cũng KHÔNG được loại bỏ TRIỆT ĐỂ mọi ứng viên là tổ tiên của bất kỳ ứng viên nào khác
     * (cách làm ở bản trước) — đã gặp thật với vietnamnet.vn: khối nội dung thật
     * (`content-detail`, 5647 ký tự) chứa vài đoạn embed/widget nhỏ lồng bên trong (các
     * `<article class="vnn-bg-sample...">`, chỉ ~200-460 ký tự — kiểu block nội dung nhúng của
     * CMS, không phải "câu chuyện liên quan"). Loại triệt để mọi tổ tiên sẽ loại luôn
     * `content-detail`, cuối cùng chỉ còn sót lại đúng 1 embed bé xíu (460 ký tự) làm
     * main_content — mất gần hết bài viết thật. Nên chỉ loại ứng viên cha khi có 1 ứng viên con
     * chiếm ĐA SỐ nội dung của cha (tỉ lệ >= ANCESTOR_REDUNDANCY_RATIO, nghĩa là cha chỉ là
     * wrapper mỏng bọc ngoài con) — còn con chỉ là mảnh nhỏ lồng bên trong (như trường hợp
     * vietnamnet) thì giữ nguyên cha vì cha mới là nội dung đầy đủ.
     */
    private function resolveContentRoot(\DOMXPath $xpath): ?\DOMNode
    {
        $candidates = [];

        foreach ($xpath->query('//article') as $node) {
            $candidates[] = [$node, mb_strlen($this->cleanText($node->textContent))];
        }

        foreach (self::MAIN_CONTENT_SELECTORS as $selector) {
            foreach ($xpath->query($selector) as $node) {
                $candidates[] = [$node, mb_strlen($this->cleanText($node->textContent))];
            }
        }

        if ($candidates === []) {
            // Fallback cuối cùng — toàn bộ <body> sau khi đã loại noise.
            return $xpath->query('//body')->item(0);
        }

        $substantial = array_values(array_filter($candidates, fn (array $c) => $c[1] >= self::MIN_CANDIDATE_CHARS));
        $pool        = $substantial !== [] ? $substantial : $candidates;

        $notRedundant = array_values(array_filter(
            $pool,
            fn (array $c) => ! $this->isRedundantAncestor($c[0], $c[1], $pool)
        ));

        $final = $notRedundant !== [] ? $notRedundant : $pool;

        usort($final, fn (array $a, array $b) => $b[1] <=> $a[1]);

        return $final[0][0];
    }

    /** @param array<array{0: \DOMNode, 1: int}> $pool */
    private function isRedundantAncestor(\DOMNode $node, int $len, array $pool): bool
    {
        foreach ($pool as [$candidateNode, $candidateLen]) {
            if ($candidateNode !== $node
                && $this->isAncestorOf($node, $candidateNode)
                && $len > 0
                && ($candidateLen / $len) >= self::ANCESTOR_REDUNDANCY_RATIO
            ) {
                return true;
            }
        }

        return false;
    }

    private function isAncestorOf(\DOMNode $ancestor, \DOMNode $node): bool
    {
        for ($current = $node->parentNode; $current !== null; $current = $current->parentNode) {
            if ($current === $ancestor) {
                return true;
            }
        }

        return false;
    }

    /**
     * Nối text theo cấu trúc DOM, tự chèn ranh giới đoạn cho thẻ block (xem BLOCK_TAGS) và
     * newline đơn cho `<br>` — thay cho `$node->textContent` (không phân biệt ranh giới thẻ,
     * gây dính chữ ở HTML minify).
     */
    private function extractBlockText(\DOMNode $node): string
    {
        if ($node instanceof \DOMText) {
            return $node->nodeValue ?? '';
        }

        if ($node->nodeName === 'br') {
            return "\n";
        }

        if (! $node instanceof \DOMElement && ! $node instanceof \DOMDocument) {
            return '';
        }

        $text = '';
        foreach ($node->childNodes as $child) {
            $text .= $this->extractBlockText($child);
        }

        return in_array($node->nodeName, self::BLOCK_TAGS, true) ? "\n\n{$text}\n\n" : $text;
    }

    private function cleanText(string $text): string
    {
        $text = preg_replace('/[ \t]+/', ' ', $text) ?? $text;
        $text = preg_replace('/[ \t]*\n[ \t]*/', "\n", $text) ?? $text;
        $text = preg_replace('/\n{3,}/', "\n\n", $text) ?? $text;

        return trim($text);
    }

    private function extractPublishDate(\DOMXPath $xpath, string $html): ?string
    {
        $meta = $this->metaContent($xpath, "//meta[@property='article:published_time']");
        if ($meta) {
            return $meta;
        }

        return $this->fromJsonLd($html, 'datePublished');
    }

    private function extractAuthor(\DOMXPath $xpath, string $html): ?string
    {
        $meta = $this->metaContent($xpath, "//meta[@name='author']");
        if ($meta) {
            return $meta;
        }

        $author = $this->fromJsonLd($html, 'author');

        if (is_array($author)) {
            return $author['name'] ?? null;
        }

        return $author;
    }

    /** Best-effort đọc JSON-LD (<script type="application/ld+json">) — không throw nếu JSON sai định dạng. */
    private function fromJsonLd(string $html, string $key): mixed
    {
        if (! preg_match_all('#<script[^>]*type=["\']application/ld\+json["\'][^>]*>(.*?)</script>#is', $html, $matches)) {
            return null;
        }

        foreach ($matches[1] as $json) {
            $decoded = json_decode(trim($json), true);

            if (! is_array($decoded)) {
                continue;
            }

            $candidates = isset($decoded[0]) && is_array($decoded[0]) ? $decoded : [$decoded];

            foreach ($candidates as $item) {
                if (isset($item[$key])) {
                    return $item[$key];
                }
            }
        }

        return null;
    }

    /**
     * Quét cả HTML thô (không chỉ text hiển thị) để bắt được cả trường hợp paywall chỉ thể hiện
     * qua class/id (VD icon khoá không có text) — nhưng PHẢI loại nội dung <style>/<script>
     * trước khi quét: đã gặp thật với vnexpress.net — CSS boilerplate dùng chung cho MỌI bài
     * (kể cả bài miễn phí) định nghĩa sẵn class ".paywall-paragraph-overlay"/"paywall-readmore"
     * trong <style>, khớp nhầm từ khoá "paywall" dù bài viết hoàn toàn đọc được đầy đủ, không hề
     * bị khoá.
     */
    private function looksLikePaywalled(\DOMXPath $xpath): bool
    {
        $bodyText = mb_strtolower($xpath->query('//body')->item(0)?->textContent ?? '');
        $bodyHtml = mb_strtolower($xpath->document->saveHTML() ?: '');
        $bodyHtml = preg_replace('#<(script|style)\b[^>]*>.*?</\1>#is', '', $bodyHtml) ?? $bodyHtml;

        foreach (self::PAYWALL_KEYWORDS as $kw) {
            if (str_contains($bodyText, $kw) || str_contains($bodyHtml, $kw)) {
                return true;
            }
        }

        return false;
    }

    /**
     * §6.1.4 (v1.3) — với ngôn ngữ không phân từ bằng khoảng trắng (Thái/Trung/Nhật...), đếm
     * theo str_word_count sẽ sai hoàn toàn (VD tiếng Thái không có khoảng trắng giữa từ) — dò
     * tỉ lệ ký tự thuộc dải Unicode CJK/Thai/Hiragana/Katakana, nếu chiếm ưu thế thì ước lượng
     * word_count = mb_strlen/2 (xấp xỉ trung bình 2 ký tự/từ, chấp nhận được vì đây chỉ dùng để
     * so ngưỡng high/medium/low, không cần chính xác tuyệt đối — spec §6.1.4 cũng chỉ yêu cầu
     * "ước lượng theo cảm nhận ngữ nghĩa", ở đây code hoá bằng heuristic gần nhất có thể).
     */
    private function countWords(string $text, string $language): int
    {
        $text = trim($text);

        if ($text === '') {
            return 0;
        }

        $totalChars = mb_strlen($text);
        preg_match_all('/[\x{0E00}-\x{0E7F}\x{4E00}-\x{9FFF}\x{3040}-\x{30FF}\x{AC00}-\x{D7A3}]/u', $text, $m);
        $cjkChars = count($m[0]);

        if ($totalChars > 0 && $cjkChars / $totalChars > 0.3) {
            return (int) round(mb_strlen(preg_replace('/\s+/', '', $text) ?? $text) / 2);
        }

        return str_word_count($text);
    }
}
