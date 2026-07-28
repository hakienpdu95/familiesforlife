<?php

namespace Modules\Post\Features\PublicReading\Http;

use App\Http\Controllers\Controller;
use Illuminate\Http\Response;
use Modules\Menu\Models\MenuItem;
use Modules\Post\Enums\ArticleFormat;
use Modules\Post\Features\AuthorHub\Support\AuthorRoleResolver;
use Modules\Post\Models\PostArticleTranslation;
use Modules\Post\Models\PostAuthorProfile;
use Modules\Post\Models\PostCategory;

class SitemapController extends Controller
{
    /**
     * Chỉ liệt kê bản dịch published CỦA LOCALE MẶC ĐỊNH — route công khai /{slug}-d{id}.html
     * không còn {locale}, nên bản dịch ở locale khác (nếu có) không có URL public nào để liệt kê.
     *
     * format=redirect bị loại — URL này chỉ bounce (302) ra domain khác, không có nội dung
     * riêng để Google index, đưa vào sitemap của CHÍNH MÌNH không có ý nghĩa.
     *
     * GEO (2026-07-28) — trước đây sitemap CHỈ có bài viết (không priority/changefreq). Mở rộng
     * thêm trang chủ/danh mục/tác giả — cùng nhóm trang vừa được thêm JSON-LD/OG lần này, đúng
     * tinh thần "Crawlability" các bài GEO nhấn mạnh (sitemap đầy đủ giúp AI crawler/Googlebot
     * khám phá hết các loại trang, không chỉ bài viết).
     */
    public function index(): Response
    {
        $translations = PostArticleTranslation::published()
            ->where('locale', config('post.default_locale'))
            ->whereHas('article', fn ($q) => $q->where('format', '!=', ArticleFormat::Redirect))
            ->orderBy('updated_at', 'desc')
            ->get(['id', 'locale', 'slug', 'updated_at']);

        $categories = PostCategory::active()->get(['slug', 'updated_at']);

        // is_public chưa đủ — cùng điều kiện route thật (AuthorHubPublicController::show()):
        // user phải còn isPlatform() (§0 v1.2), tránh liệt kê URL sẽ 404 nếu điều kiện đổi sau.
        $authorProfiles = PostAuthorProfile::where('is_public', true)
            ->with('user')
            ->get(['id', 'uuid', 'slug', 'user_id', 'updated_at'])
            ->filter(fn (PostAuthorProfile $profile) => $profile->user && AuthorRoleResolver::isEligible($profile->user))
            ->values();

        return response()
            ->view('post::public.sitemap', compact('translations', 'categories', 'authorProfiles'))
            ->header('Content-Type', 'text/xml');
    }

    /**
     * GEO (2026-07-28) — trước đây `public/robots.txt` là file TĨNH (`User-agent: *\nDisallow:`,
     * cho crawl toàn site nhưng KHÔNG khai `Sitemap:`) — chuyển sang route động để tự nhúng URL
     * sitemap tuyệt đối đúng domain đang chạy (`route()`), không hard-code domain vào file tĩnh
     * (sẽ sai domain khi đổi môi trường dev/staging/prod). File tĩnh cùng tên đã bị xoá — nếu
     * không, PHP built-in server/webserver sẽ ưu tiên serve file tĩnh, route này không bao giờ chạy.
     */
    /**
     * GEO đợt 4 (2026-07-28) — khai RÕ TỪNG bot AI thay vì chỉ ngầm định qua `User-agent: *`
     * ở trên (dòng đó ĐÃ cho phép mọi bot rồi — các dòng dưới đây chỉ để RÕ RÀNG/tài liệu hoá ý
     * định "cố tình cho phép AI crawl", không đổi hành vi thực tế). 3 nhóm theo phân loại phổ biến:
     * training crawler (dùng để train model), retrieval crawler (dùng để trả lời truy vấn thời gian
     * thực), user-triggered fetcher (bot chạy khi người dùng tự dán link vào chat AI).
     */
    private const AI_CRAWLER_USER_AGENTS = [
        'GPTBot', 'ChatGPT-User', 'OAI-SearchBot',       // OpenAI
        'ClaudeBot', 'anthropic-ai', 'Claude-User',       // Anthropic
        'PerplexityBot', 'Perplexity-User',               // Perplexity
        'Google-Extended',                                // Google AI (Gemini/AI Overviews training)
        'CCBot',                                          // Common Crawl — nguồn train của nhiều LLM
        'cohere-ai',                                      // Cohere (GEO đợt 8, 2026-07-28 — thiếu ở đợt 4)
    ];

    public function robots(): Response
    {
        // GEO đợt 6 (2026-07-28) — `Allow: /llms.txt` phải khai trong CHÍNH group `User-agent: *`
        // này (không đặt rời sau vòng lặp bên dưới) — 1 dòng Allow/Disallow luôn thuộc về group
        // User-agent GẦN NHẤT phía trên nó theo đúng cú pháp robots.txt, đặt sai chỗ sẽ vô tình
        // gán nhầm cho bot cuối cùng trong vòng lặp, không phải cho `*`. `Disallow:` rỗng ở đây đã
        // cho phép ngầm rồi, dòng Allow chỉ phòng trường hợp sau này ai đó thêm 1 Disallow tổng
        // quát mà quên loại trừ llms.txt — phá mất mục đích của nó (đúng cảnh báo "Disallow
        // LLMs.txt in robots.txt = defeats purpose" từ nguồn tham khảo).
        $lines = ['User-agent: *', 'Disallow:', 'Allow: /llms.txt', ''];

        foreach (self::AI_CRAWLER_USER_AGENTS as $userAgent) {
            $lines[] = "User-agent: {$userAgent}";
            $lines[] = 'Allow: /';
            $lines[] = '';
        }

        $lines[] = 'Sitemap: ' . route('post.public.sitemap');

        return response(implode("\n", $lines) . "\n", 200, ['Content-Type' => 'text/plain']);
    }

    /**
     * GEO đợt 4 (2026-07-28) — `llms.txt` (quy ước llmstxt.org): file Markdown mô tả site cho
     * LLM đọc trực tiếp khi cần ngữ cảnh (KHÔNG phải cơ chế crawl/index, không thay thế
     * sitemap/robots.txt) — "chi phí thấp, đáng làm" theo khuyến nghị nguồn tham khảo, không phải
     * đòn bẩy xếp hạng. Chỉ liệt kê category CẤP GỐC (root, is_active) — category con quá nhiều
     * sẽ làm file dài không cần thiết, LLM tự theo link sitemap nếu cần đầy đủ.
     */
    public function llms(): Response
    {
        // Cùng bộ lọc đã áp dụng cho dashboard/core-idea-extractor/category-foundations
        // (2026-07-28) — 88 post_categories nhưng ~44 cái là taxonomy CŨ đã bị thay bằng Menu
        // chính mới (2026-07-27), không còn ai truy cập qua điều hướng thật. llms.txt là bản tóm
        // tắt CÔ ĐỌNG cho AI, liệt kê hết cả taxonomy chết sẽ thành danh sách nhiễu vô ích — khác
        // sitemap.xml (vẫn cố tình liệt kê MỌI category active, kể cả không có trong menu, vì
        // sitemap tồn tại chính để giúp crawler tìm trang KHÔNG có link điều hướng tới).
        $menuCategoryIds = MenuItem::query()->where('location', 'header')->whereNotNull('category_id')->pluck('category_id');

        $categories = PostCategory::active()->root()
            ->whereIn('id', $menuCategoryIds)
            ->get(['name', 'slug']);

        return response()
            ->view('post::public.llms', compact('categories'))
            ->header('Content-Type', 'text/plain; charset=UTF-8');
    }
}
