<?php
namespace App\Enums;

enum PermissionEnum: string
{
    // ══ CEO DASHBOARD ══════════════════════════════════════════════
    // CEO=Full | Ops=Limited | AI_OP=Limited | Admin=Config | Viewer=View limited
    case CEO_DASH_FULL   = 'ceo_dashboard.full';    // CEO
    case CEO_DASH_VIEW   = 'ceo_dashboard.view';    // Ops(limited), AI_OP(limited), Viewer
    case CEO_DASH_CONFIG = 'ceo_dashboard.config';  // System Admin

    // ══ CRM LEADS ══════════════════════════════════════════════════
    // CEO=Full | Sales=Assigned | Ops=Limited | Marketing=Source view | AI_OP=Limited | Admin=Config | Viewer=No
    case LEADS_VIEW_ALL      = 'leads.view_all';      // Ops(limited=view only), AI_OP(limited)
    case LEADS_VIEW_ASSIGNED = 'leads.view_assigned'; // Sales
    case LEADS_VIEW_SOURCE   = 'leads.view_source';   // Marketing — SOURCE VIEW
    case LEADS_CREATE        = 'leads.create';        // CEO, Sales
    case LEADS_EDIT          = 'leads.edit';          // CEO, Sales(own via Policy)
    case LEADS_DELETE        = 'leads.delete';        // CEO only
    case LEADS_ASSIGN        = 'leads.assign';        // CEO
    case LEADS_CONFIG          = 'leads.config';           // System Admin (độc lập, không kèm view data)
    case LEADS_EXPORT          = 'leads.export';           // CEO, Ops — xuất Excel
    case LEADS_MANAGE_PIPELINE = 'leads.manage_pipeline';  // System Admin — thêm/sửa/xóa stages
    case LEADS_MANAGE_SOURCES  = 'leads.manage_sources';   // System Admin — thêm/sửa/xóa sources
    case LEADS_MANAGE_TAGS     = 'leads.manage_tags';      // Ops, System Admin — quản lý tags

    // ══ CRM CUSTOMERS ═════════════════════════════════════════════
    // CEO=Full | Sales=Assigned | Ops=View+Edit | Marketing=View | Admin=Config
    case CUSTOMERS_VIEW_ALL      = 'customers.view_all';      // CEO, Ops, Marketing, AI_OP
    case CUSTOMERS_VIEW_ASSIGNED = 'customers.view_assigned'; // Sales
    case CUSTOMERS_CREATE        = 'customers.create';        // CEO, Sales, Ops
    case CUSTOMERS_EDIT          = 'customers.edit';          // CEO, Sales(own), Ops
    case CUSTOMERS_DELETE        = 'customers.delete';        // CEO only
    case CUSTOMERS_EXPORT        = 'customers.export';        // CEO, Ops
    case CUSTOMERS_CONFIG        = 'customers.config';        // System Admin — custom fields

    // ══ SALES AI ═══════════════════════════════════════════════════
    // CEO=Full | Sales=Use | Marketing=Limited | AI_OP=Config prompt | Admin=Config
    case SALES_AI_VIEW          = 'sales_ai.view';          // CEO(full), Marketing(limited)
    case SALES_AI_USE           = 'sales_ai.use';           // Sales — gọi AI, nhận output
    case SALES_AI_CONFIG_PROMPT = 'sales_ai.config_prompt'; // AI Operator
    case SALES_AI_CONFIG        = 'sales_ai.config';        // System Admin

    // ══ WORKFLOW ═══════════════════════════════════════════════════
    // CEO=Monitor | Sales=Limited | Ops=Monitor/Edit | Marketing=Limited | HR=Limited
    // AI_OP=AI config | Admin=Full config
    case WORKFLOW_MONITOR      = 'workflow.monitor';      // CEO, Ops
    case WORKFLOW_EDIT         = 'workflow.edit';         // Ops (Monitor/Edit = monitor+edit)
    case WORKFLOW_VIEW_LIMITED = 'workflow.view_limited'; // Sales, Marketing, HR
    case WORKFLOW_AI_CONFIG    = 'workflow.ai_config';    // AI Operator
    case WORKFLOW_FULL_CONFIG  = 'workflow.full_config';  // System Admin

    // ══ USERS ══════════════════════════════════════════════════════
    // CEO=View | HR=Limited | Admin=Full
    case USERS_VIEW   = 'users.view';   // CEO
    case USERS_HR     = 'users.hr';     // HR (tạo user nội bộ, onboarding)
    case USERS_MANAGE = 'users.manage'; // System Admin

    // ══ ROLES & PERMISSIONS ════════════════════════════════════════
    // Admin=Full only
    case ROLES_MANAGE = 'roles.manage';

    // ══ REPORTS ════════════════════════════════════════════════════
    case REPORTS_FULL      = 'reports.full';      // CEO, Admin
    case REPORTS_PERSONAL  = 'reports.personal';  // Sales (cá nhân)
    case REPORTS_TEAM      = 'reports.team';      // Sales (team)
    case REPORTS_OPS       = 'reports.ops';       // Ops
    case REPORTS_MARKETING = 'reports.marketing'; // Marketing
    case REPORTS_HR        = 'reports.hr';        // HR
    case REPORTS_SHARED    = 'reports.shared';    // Viewer

    // ══ ASSESSMENT (Chấm điểm khảo sát) ═══════════════════════════
    // CEO=View | Ops=View | AI_OP=Config+Reprocess | Admin=Full
    case ASSESSMENT_VIEW      = 'assessment.view';      // CEO, Ops, AI_OP — xem danh sách assessments
    case ASSESSMENT_CONFIG    = 'assessment.config';    // AI_OP, Admin — wizard cấu hình
    case ASSESSMENT_RESULTS   = 'assessment.results';   // CEO, Ops, AI_OP — xem kết quả
    case ASSESSMENT_REPROCESS = 'assessment.reprocess'; // AI_OP, Admin — force recalculate

    // ══ SUBSCRIPTION ═══════════════════════════════════════════════
    case SUBSCRIPTION_VIEW    = 'subscription.view';
    case SUBSCRIPTION_MANAGE  = 'subscription.manage';
    case SUBSCRIPTION_BILLING = 'subscription.billing';
    case SUBSCRIPTION_ADMIN   = 'subscription.admin';

    // ══ SYSTEM ═════════════════════════════════════════════════════
    case INTEGRATION_MANAGE = 'integration.manage';
    case AUDIT_VIEW         = 'audit.view';
    case SYSTEM_CONFIG      = 'system.config';

    // ══ PRODUCT (Danh mục Sản phẩm & Dịch vụ — catalog dùng chung cho Post CTA Box) ═
    // Marketing/Sales=Soạn thảo | CEO/Ops=Full | System Admin=Full + quản lý danh mục | còn lại=View
    case PRODUCT_CATEGORY_MANAGE = 'product_category.manage';
    case PRODUCT_VIEW            = 'product.view';
    case PRODUCT_CREATE          = 'product.create';
    case PRODUCT_EDIT            = 'product.edit';
    case PRODUCT_DELETE          = 'product.delete';

    // ══ POST (Bài viết theo danh mục + Product CTA Box) ═════════════
    // Marketing=Soạn thảo | CEO/Ops=Duyệt & publish | System_Admin=Full + quản lý danh mục | còn lại=View (bài đã published)
    case POST_CATEGORY_MANAGE = 'post_category.manage';
    case POST_TAG_MANAGE      = 'post_tag.manage';
    case POST_ARTICLE_VIEW    = 'post_article.view';
    case POST_ARTICLE_CREATE  = 'post_article.create';
    case POST_ARTICLE_EDIT    = 'post_article.edit';
    case POST_ARTICLE_DELETE  = 'post_article.delete';
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
    case EVENT_VIEW            = 'event.view';
    case EVENT_EDIT            = 'event.edit';           // sửa nội dung trước Approve (§6.1)
    case EVENT_MODERATE        = 'event.moderate';       // Approve/Reject
    case EVENT_PUBLISH         = 'event.publish';
    case EVENT_UNPUBLISH       = 'event.unpublish';
    case EVENT_DELETE          = 'event.delete';

    // ══ AICEM (AI Context Engineering Module — trợ lý AI cho Post & Product) ═
    // CEO=View | Marketing=Use | Ops=View limited | AI_OP=Config prompt | Admin=Config
    case AICEM_VIEW          = 'aicem.view';           // xem knowledge base/lịch sử (read-only)
    case AICEM_USE           = 'aicem.use';            // chạy workflow trên bài viết/sản phẩm, accept/reject suggestion
    case AICEM_CONFIG_PROMPT = 'aicem.config_prompt';  // sửa knowledge base, template, workflow
    case AICEM_CONFIG        = 'aicem.config';         // cấu hình provider/API key/hạn mức chi phí

    // ══ MENU (Điều hướng menu — header/footer, decoupled khỏi PostCategory) ══
    // spec/Menu_Navigation_Technical_Specification.md §6.3 — chỉ System_Admin quản lý,
    // cùng nguyên tắc POST_CATEGORY_MANAGE (cấu trúc điều hướng là việc của Admin).
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

    // ══ BREAKING NEWS (Tin nóng/tin chạy ghim đầu trang chủ — tài sản nền tảng) ═══
    // spec/Breaking_News_Ticker_Technical_Specification.md §6.3 — gán cho platform_ops +
    // platform_content_head (BreakingNewsPermissionSeeder), KHÔNG qua config/permissions.php
    // (Lớp B) — cùng nguyên tắc BANNER_MANAGE/OCOP_MANAGE/PAGE_MANAGE/CORE_IDEA_EXTRACTOR_USE.
    case BREAKING_NEWS_MANAGE = 'breaking_news.manage';

    // ══ REAL ESTATE (Tin rao bán/thuê bất động sản — listing của Organization) ═══
    // spec/RealEstateForSale_Technical_Specification.md §6 — Lớp B, qua config/permissions.php
    // + RoleEnum (CEO/SALES/OPS/MARKETING: view+create+edit; ADMIN: thêm delete) — cùng
    // nguyên tắc PRODUCT_VIEW/PRODUCT_CREATE/PRODUCT_EDIT/PRODUCT_DELETE.
    case REAL_ESTATE_VIEW   = 'real_estate.view';
    case REAL_ESTATE_CREATE = 'real_estate.create';
    case REAL_ESTATE_EDIT   = 'real_estate.edit';
    case REAL_ESTATE_DELETE = 'real_estate.delete';

    // ══ VIDEO (Thư viện video YouTube độc lập — tài sản nền tảng, không qua quy trình duyệt) ═══
    // spec/Video_Management_Technical_Specification.md §6.7 — gán cho platform_ops +
    // platform_content_head (VideoPermissionSeeder), KHÔNG qua config/permissions.php (Lớp B)
    // — cùng nguyên tắc BANNER_MANAGE/OCOP_MANAGE/PAGE_MANAGE/BREAKING_NEWS_MANAGE.
    case VIDEO_MANAGE = 'video.manage';
}