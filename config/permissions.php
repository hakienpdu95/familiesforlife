<?php
// Thêm permission mới: chỉ sửa file này + chạy php artisan permissions:sync
use App\Enums\RoleEnum as R;
use App\Enums\PermissionEnum as P;

return [

    R::CEO->value => [
        // CEO Dashboard: Full
        P::CEO_DASH_FULL->value,
        // Sales AI: Full (view + use)
        P::SALES_AI_VIEW->value,
        P::SALES_AI_USE->value,
        // Workflow: Monitor only — KHÔNG có edit
        P::WORKFLOW_MONITOR->value,
        // Users: View
        P::USERS_VIEW->value,
        // Product: Full (CEO tự quản lý catalog sản phẩm/dịch vụ dùng cho Post CTA Box)
        P::PRODUCT_VIEW->value,
        P::PRODUCT_CREATE->value,
        P::PRODUCT_EDIT->value,
        // Real Estate: View + Create + Edit (CEO tự đăng tin BĐS — cùng nhóm quản lý Product)
        P::REAL_ESTATE_VIEW->value,
        P::REAL_ESTATE_CREATE->value,
        P::REAL_ESTATE_EDIT->value,
        // Post: View + Duyệt & publish (không tự soạn thảo — Marketing soạn)
        P::POST_ARTICLE_VIEW->value,
        P::POST_ARTICLE_PUBLISH->value,
        // AICEM: View (full) — xem knowledge base/lịch sử, không tự chạy workflow
        P::AICEM_VIEW->value,
    ],

    R::SALES->value => [
        // Sales AI: Use (dùng output, không config)
        P::SALES_AI_USE->value,
        // Workflow: Limited (visibility=public)
        P::WORKFLOW_VIEW_LIMITED->value,
        // Product: Soạn thảo (Sales tự thêm sản phẩm/dịch vụ giới thiệu cho khách)
        P::PRODUCT_VIEW->value,
        P::PRODUCT_CREATE->value,
        P::PRODUCT_EDIT->value,
        // Real Estate: View + Create + Edit (Sales tự đăng tin BĐS)
        P::REAL_ESTATE_VIEW->value,
        P::REAL_ESTATE_CREATE->value,
        P::REAL_ESTATE_EDIT->value,
        // Post: View only (xem bài đã publish)
        P::POST_ARTICLE_VIEW->value,
    ],

    R::OPS->value => [
        // CEO Dashboard: Limited (view, không có AI brief, không có approve)
        P::CEO_DASH_VIEW->value,
        // Workflow: Monitor/Edit
        P::WORKFLOW_MONITOR->value,
        P::WORKFLOW_EDIT->value,
        // Product: Full (Ops quản lý catalog sản phẩm/dịch vụ dùng cho Post CTA Box)
        P::PRODUCT_VIEW->value,
        P::PRODUCT_CREATE->value,
        P::PRODUCT_EDIT->value,
        // Real Estate: View + Create + Edit (Ops quản lý tin BĐS)
        P::REAL_ESTATE_VIEW->value,
        P::REAL_ESTATE_CREATE->value,
        P::REAL_ESTATE_EDIT->value,
        // Post: View + Duyệt & publish
        P::POST_ARTICLE_VIEW->value,
        P::POST_ARTICLE_PUBLISH->value,
        // AICEM: View (limited) — giống cách Ops xem Sales AI
        P::AICEM_VIEW->value,
    ],

    R::MARKETING->value => [
        // Sales AI: Limited (view, không use/config)
        P::SALES_AI_VIEW->value,
        // Workflow: Limited
        P::WORKFLOW_VIEW_LIMITED->value,
        // Product: Soạn thảo (Marketing biên tập catalog sản phẩm dùng cho Post CTA Box)
        P::PRODUCT_VIEW->value,
        P::PRODUCT_CREATE->value,
        P::PRODUCT_EDIT->value,
        // Real Estate: View + Create + Edit (Marketing tự đăng tin BĐS)
        P::REAL_ESTATE_VIEW->value,
        P::REAL_ESTATE_CREATE->value,
        P::REAL_ESTATE_EDIT->value,
        // Post: Soạn thảo (Marketing tự viết/sửa/xoá bài, gửi duyệt — CEO/Ops publish)
        P::POST_ARTICLE_VIEW->value,
        P::POST_ARTICLE_CREATE->value,
        P::POST_ARTICLE_EDIT->value,
        P::POST_ARTICLE_DELETE->value,
        // AICEM: Use — người thực chạy workflow khi soạn bài/sản phẩm
        P::AICEM_USE->value,
    ],

    R::HR->value => [
        // Workflow: Limited
        P::WORKFLOW_VIEW_LIMITED->value,
        // Users: Limited (tạo user nội bộ, onboarding)
        P::USERS_HR->value,
        // Product: View only
        P::PRODUCT_VIEW->value,
        // Post: View only
        P::POST_ARTICLE_VIEW->value,
    ],

    R::AI_OP->value => [
        // CEO Dashboard: Limited
        P::CEO_DASH_VIEW->value,
        // Sales AI: Config prompt
        P::SALES_AI_CONFIG_PROMPT->value,
        // Workflow: Monitor + AI config
        P::WORKFLOW_MONITOR->value,
        P::WORKFLOW_AI_CONFIG->value,
        // Product: View only
        P::PRODUCT_VIEW->value,
        // Post: View only
        P::POST_ARTICLE_VIEW->value,
        // AICEM: Config prompt — chỉnh SKILL/brand/persona/template, xem version history, rollback
        P::AICEM_CONFIG_PROMPT->value,
    ],

    R::ADMIN->value => [
        // Config trên tất cả module (độc lập với data)
        P::CEO_DASH_CONFIG->value,
        P::SALES_AI_CONFIG->value,
        P::WORKFLOW_MONITOR->value,
        P::WORKFLOW_EDIT->value,
        P::WORKFLOW_FULL_CONFIG->value,
        // User & roles management
        P::USERS_MANAGE->value,
        P::ROLES_MANAGE->value,
        // System
        P::INTEGRATION_MANAGE->value,
        P::AUDIT_VIEW->value,
        P::SYSTEM_CONFIG->value,
        // Product: Full manage (catalog sản phẩm/dịch vụ + quản lý danh mục)
        P::PRODUCT_VIEW->value,
        P::PRODUCT_CREATE->value,
        P::PRODUCT_EDIT->value,
        P::PRODUCT_DELETE->value,
        P::PRODUCT_CATEGORY_MANAGE->value,
        // Real Estate: Full manage (tin BĐS bán/thuê)
        P::REAL_ESTATE_VIEW->value,
        P::REAL_ESTATE_CREATE->value,
        P::REAL_ESTATE_EDIT->value,
        P::REAL_ESTATE_DELETE->value,
        // Post: Full manage (bài viết + quản lý danh mục)
        P::POST_ARTICLE_VIEW->value,
        P::POST_ARTICLE_CREATE->value,
        P::POST_ARTICLE_EDIT->value,
        P::POST_ARTICLE_DELETE->value,
        P::POST_ARTICLE_PUBLISH->value,
        P::POST_CATEGORY_MANAGE->value,
        // Menu: Full manage (điều hướng header/footer)
        P::MENU_MANAGE->value,
        // AICEM: Config — provider, API key, hạn mức chi phí theo Organization
        P::AICEM_CONFIG->value,
    ],

    R::VIEWER->value => [
        // CEO Dashboard: View limited
        P::CEO_DASH_VIEW->value,
        // Product: View only
        P::PRODUCT_VIEW->value,
        // Post: View only
        P::POST_ARTICLE_VIEW->value,
    ],
];