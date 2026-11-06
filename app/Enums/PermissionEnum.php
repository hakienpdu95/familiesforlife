<?php

namespace App\Enums;

enum PermissionEnum: string
{
    // ══ PRODUCT (Danh mục Sản phẩm & Dịch vụ — catalog dùng chung cho Post CTA Box) ═
    case PRODUCT_CATEGORY_MANAGE = 'product_category.manage';
    case PRODUCT_VIEW = 'product.view';
    case PRODUCT_CREATE = 'product.create';
    case PRODUCT_EDIT = 'product.edit';
    case PRODUCT_DELETE = 'product.delete';

    // ══ POST (Bài viết theo danh mục + Product CTA Box) ═════════════
    case POST_CATEGORY_MANAGE = 'post_category.manage';
    case POST_TAG_MANAGE = 'post_tag.manage';
    case POST_ARTICLE_VIEW = 'post_article.view';
    case POST_ARTICLE_CREATE = 'post_article.create';
    case POST_ARTICLE_EDIT = 'post_article.edit';
    case POST_ARTICLE_DELETE = 'post_article.delete';
    case POST_ARTICLE_PUBLISH = 'post_article.publish';
    case POST_ARTICLE_UNPUBLISH = 'post_article.unpublish'; // tách riêng khỏi publish — Ops có thể publish nhưng không được gỡ bừa
    case POST_ARTICLE_MANAGE_SPONSORSHIP = 'post_article.manage_sponsorship'; // bật/tắt is_sponsored — tách khỏi post_article.edit thường

    // ══ EVENT (Quản lý sự kiện — độc giả nộp công khai, toà soạn duyệt) ═════
    // spec/Event_Management_Technical_Specification.md §9 — approve/reject/publish/archive
    // dùng role-helper (isPlatformContentEditor/Head) ở EventPolicy, KHÔNG qua permission
    // string (cùng nguyên tắc PostArticlePolicy) — EVENT_MODERATE/EVENT_PUBLISH/EVENT_UNPUBLISH
    // giữ lại để tài liệu hoá đầy đủ hành động tồn tại, cùng cách post_article.publish/
    // unpublish vẫn còn dù không role nào được cấp qua Spatie nữa.
    case EVENT_CATEGORY_MANAGE = 'event_category.manage';
    case EVENT_VIEW = 'event.view';
    case EVENT_EDIT = 'event.edit';           // sửa nội dung trước Approve (§6.1)
    case EVENT_MODERATE = 'event.moderate';       // Approve/Reject
    case EVENT_PUBLISH = 'event.publish';
    case EVENT_UNPUBLISH = 'event.unpublish';
    case EVENT_DELETE = 'event.delete';

    // ══ AICEM (AI Context Engineering Module — trợ lý AI cho Post & Product) ═
    case AICEM_VIEW = 'aicem.view';           // xem knowledge base/lịch sử (read-only)
    case AICEM_USE = 'aicem.use';            // chạy workflow trên bài viết/sản phẩm, accept/reject suggestion
    case AICEM_CONFIG_PROMPT = 'aicem.config_prompt';  // sửa knowledge base, template, workflow
    case AICEM_CONFIG = 'aicem.config';         // cấu hình provider/API key/hạn mức chi phí

    // ══ MENU (Điều hướng menu — header/footer, decoupled khỏi PostCategory) ══
    // spec/Menu_Navigation_Technical_Specification.md §6.3
    case MENU_MANAGE = 'menu.manage';

    // ══ BANNER (Banner quảng cáo/thông báo — nhiều placement, targeting theo category) ═══
    // spec/Banner_Management_Technical_Specification.md §6.3 — gán cho platform_ops +
    // platform_content_head (BannerPermissionSeeder), KHÔNG qua config/permissions.php (Lớp B)
    // — cùng nguyên tắc EVENT_VIEW/EVENT_CATEGORY_MANAGE.
    case BANNER_MANAGE = 'banner.manage';

    // ══ OCOP (Sản phẩm đặc trưng OCOP theo tỉnh — hạng sao, nhà sản xuất) ═══
    // spec/Province_Showcase_Technical_Specification.md §6.1 — gán cho platform_ops +
    // platform_content_head (OcopPermissionSeeder), KHÔNG qua config/permissions.php (Lớp B)
    // — cùng nguyên tắc BANNER_MANAGE.
    case OCOP_MANAGE = 'ocop.manage';

    case OCOP_SUBJECT_MANAGE = 'ocop_subject.manage';

    case PROVINCE_MANAGE = 'province.manage';

    // ══ HERITAGE (Di tích/di sản có cấu trúc — loại hình, xếp hạng, toạ độ; trục liên kết
    // Post/Event/Ocop) ═══
    // spec/Heritage_Technical_Specification.md §4 — gán cho platform_ops + platform_content_head
    // (HeritagePermissionSeeder), KHÔNG qua config/permissions.php (Lớp B) — cùng nguyên tắc
    // BANNER_MANAGE/OCOP_MANAGE.
    case HERITAGE_MANAGE = 'heritage.manage';

    // ══ PAGE (Trang tĩnh — Giới thiệu/Liên hệ/Điều khoản..., tài sản nền tảng) ═══
    // spec/Page_Static_Pages_Technical_Specification.md §7 — gán cho platform_ops +
    // platform_content_head (PagePermissionSeeder), KHÔNG qua config/permissions.php (Lớp B)
    // — cùng nguyên tắc BANNER_MANAGE/OCOP_MANAGE.
    case PAGE_MANAGE = 'page.manage';

    // ══ CORE IDEA EXTRACTOR (trích xuất dữ liệu thô từ 1 URL bài viết bất kỳ — nghiên cứu ý
    // tưởng viết bài, không phải nội dung nền tảng) ═══
    // spec/CoreIdeaExtractor.md — gán cho platform_content_editor + platform_content_head
    // (CoreIdeaExtractorPermissionSeeder), KHÔNG qua config/permissions.php (Lớp B) — cùng
    // nguyên tắc BANNER_MANAGE/OCOP_MANAGE/PAGE_MANAGE.
    case CORE_IDEA_EXTRACTOR_USE = 'core_idea_extractor.use';

    // ══ CONTENT FOUNDATION (ngữ cảnh biên tập bền vững theo PostCategory — tách từ
    // CoreIdeaExtractor để dùng chung bởi mọi công cụ nghiên cứu ý tưởng nội dung, VD
    // CoreIdeaExtractor/VideoIdeaExtractor) ═══
    // spec/CoreIdeaExtractor.md §12 — gán cho platform_content_editor/platform_content_head/
    // platform_section_editor (ContentFoundationPermissionSeeder), KHÔNG qua config/permissions.php
    // (Lớp B) — cùng nguyên tắc CORE_IDEA_EXTRACTOR_USE.
    case CONTENT_FOUNDATION_USE = 'content_foundation.use';

    // ══ VIDEO IDEA EXTRACTOR (trích xuất dữ liệu thô từ transcript video dán tay — nghiên cứu ý
    // tưởng/chủ đề làm video mới, không phải nội dung nền tảng) ═══
    // Cùng nguyên tắc CORE_IDEA_EXTRACTOR_USE — gán cho platform_content_editor/
    // platform_content_head/platform_section_editor (VideoIdeaExtractorPermissionSeeder), KHÔNG
    // qua config/permissions.php (Lớp B).
    case VIDEO_IDEA_EXTRACTOR_USE = 'video_idea_extractor.use';

    // ══ CONTENT OUTLINES (soạn prompt research/dàn ý nội dung SEO để dán sang AI ngoài — không
    // gọi AI Provider trong app, khác CORE_IDEA_EXTRACTOR_USE/VIDEO_IDEA_EXTRACTOR_USE) ═══
    // spec/ContentOutlines_Technical_Specification.md §6 — gán cho platform_content_editor/
    // platform_content_head/platform_section_editor (ContentOutlinesPermissionSeeder), KHÔNG qua
    // config/permissions.php (Lớp B) — cùng nguyên tắc CORE_IDEA_EXTRACTOR_USE.
    case CONTENT_OUTLINES_USE = 'content_outlines.use';

    // ══ BREAKING NEWS (Tin nóng/tin chạy ghim đầu trang chủ — tài sản nền tảng) ═══
    // spec/Breaking_News_Ticker_Technical_Specification.md §6.3 — gán cho platform_ops +
    // platform_content_head (BreakingNewsPermissionSeeder), KHÔNG qua config/permissions.php
    // (Lớp B) — cùng nguyên tắc BANNER_MANAGE/OCOP_MANAGE/PAGE_MANAGE/CORE_IDEA_EXTRACTOR_USE.
    case BREAKING_NEWS_MANAGE = 'breaking_news.manage';

    // ══ REAL ESTATE (Tin rao bán/thuê bất động sản) ═══
    // spec/RealEstateForSale_Technical_Specification.md §6
    case REAL_ESTATE_VIEW = 'real_estate.view';
    case REAL_ESTATE_CREATE = 'real_estate.create';
    case REAL_ESTATE_EDIT = 'real_estate.edit';
    case REAL_ESTATE_DELETE = 'real_estate.delete';

    // ══ VIDEO (Thư viện video YouTube độc lập — tài sản nền tảng, không qua quy trình duyệt) ═══
    // spec/Video_Management_Technical_Specification.md §6.7 — gán cho platform_ops +
    // platform_content_head (VideoPermissionSeeder), KHÔNG qua config/permissions.php (Lớp B)
    // — cùng nguyên tắc BANNER_MANAGE/OCOP_MANAGE/PAGE_MANAGE/BREAKING_NEWS_MANAGE.
    case VIDEO_MANAGE = 'video.manage';

    // ══ PLAYLIST (Danh sách phát đa nội dung — gom Video + Bài viết theo chủ đề biên tập, tài
    // sản nền tảng, không qua quy trình duyệt) ═══
    // spec/Playlist_Technical_Specification.md §0/§6.5 — gán cho platform_ops +
    // platform_content_head (PlaylistPermissionSeeder), KHÔNG qua config/permissions.php (Lớp B)
    // — cùng nguyên tắc VIDEO_MANAGE/BANNER_MANAGE/PAGE_MANAGE, nhưng permission RIÊNG (không
    // mượn video.manage) vì Playlist đọc dữ liệu xuyên module (Video + Post).
    case PLAYLIST_MANAGE = 'playlist.manage';

    // ══ CONTENT CALENDAR (Lịch biên tập & kế hoạch nội dung — hàng đợi ý tưởng đã chọn + lịch
    // xuất bản, nối giữa CoreIdeaExtractor và Post) ═══
    // spec/ContentCalendar_Technical_Specification.md §6 — gán cho platform_content_creator/
    // section_editor/content_editor/content_head (manage) + platform_viewer (view only)
    // (ContentCalendarPermissionSeeder), KHÔNG qua config/permissions.php (Lớp B) — cùng nguyên
    // tắc CORE_IDEA_EXTRACTOR_USE/BANNER_MANAGE/OCOP_MANAGE/PAGE_MANAGE.
    case CONTENT_CALENDAR_VIEW = 'content_calendar.view';
    case CONTENT_CALENDAR_MANAGE = 'content_calendar.manage';

    // ══ PROMPT FRAMEWORK STUDIO (thư viện 13 framework prompt engineering + form sinh prompt có
    // cấu trúc — không gọi AI Provider trong app, người dùng tự copy-paste sang AI ngoài) ═══
    // spec/PromptFrameworkStudio_Technical_Specification.md §6 — gán cho platform_content_editor/
    // platform_content_head/platform_section_editor (PromptFrameworkStudioPermissionSeeder),
    // KHÔNG qua config/permissions.php (Lớp B) — cùng nguyên tắc CONTENT_OUTLINES_USE.
    case PROMPT_FRAMEWORK_STUDIO_USE = 'prompt_framework_studio.use';

    // ══ AI VIDEO STUDIO TEMPLATE (quản lý Director Prompt Template theo Project → nhiều Shot,
    // ghép prompt để dán sang tool ảnh/video AI ngoài — không gọi AI Provider trong app) ═══
    // spec/AIVideoStudioTemplate_Technical_Specification.md §5 — gán cho platform_content_editor/
    // platform_content_head/platform_section_editor (AIVideoStudioTemplatePermissionSeeder),
    // KHÔNG qua config/permissions.php (Lớp B) — cùng nguyên tắc CONTENT_OUTLINES_USE/
    // PROMPT_FRAMEWORK_STUDIO_USE.
    case AI_VIDEO_STUDIO_TEMPLATE_USE = 'ai_video_studio_template.use';

    // ══ VIDEO SERIES PROMPT STUDIO (thiết kế kiến trúc cho CẢ CHUỖI video nhiều tập — Series
    // Bible: concept, khung 1 tập chuẩn lặp lại, dàn ý 5-10 tập đầu tiên — KHÔNG gọi AI Provider
    // trong app, người dùng tự copy-paste sang AI ngoài) ═══
    // gán cho platform_content_editor/platform_content_head/platform_section_editor
    // (VideoSeriesPromptStudioPermissionSeeder), KHÔNG qua config/permissions.php (Lớp B) — cùng
    // nguyên tắc CONTENT_OUTLINES_USE/PROMPT_FRAMEWORK_STUDIO_USE.
    case VIDEO_SERIES_PROMPT_STUDIO_USE = 'video_series_prompt_studio.use';

    // ══ BULK IDEATION PROMPT STUDIO (spec/BulkIdeationPromptStudio.md — ghép prompt "nhào nặn" ý
    // tưởng bài viết từ 1 mớ từ khóa/câu hỏi/ý tưởng vụn vặt dán rời rạc — KHÔNG gọi AI Provider
    // trong app, người dùng tự copy-paste sang AI ngoài) ═══
    // gán cho platform_content_editor/platform_content_head/platform_section_editor
    // (BulkIdeationPromptStudioPermissionSeeder), KHÔNG qua config/permissions.php (Lớp B) — cùng
    // nguyên tắc CONTENT_OUTLINES_USE/PROMPT_FRAMEWORK_STUDIO_USE/VIDEO_SERIES_PROMPT_STUDIO_USE.
    case BULK_IDEATION_PROMPT_STUDIO_USE = 'bulk_ideation_prompt_studio.use';
}
