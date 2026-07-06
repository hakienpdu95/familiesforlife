<?php
// Thêm permission mới: chỉ sửa file này + chạy php artisan permissions:sync
use App\Enums\RoleEnum as R;
use App\Enums\PermissionEnum as P;

return [

    R::CEO->value => [
        // CEO Dashboard: Full
        P::CEO_DASH_FULL->value,
        // Subscription: View + Manage + Billing
        P::SUBSCRIPTION_VIEW->value,
        P::SUBSCRIPTION_MANAGE->value,
        P::SUBSCRIPTION_BILLING->value,
        // CRM Leads: Full (view all + CRUD + assign + export)
        P::LEADS_VIEW_ALL->value,
        P::LEADS_CREATE->value,
        P::LEADS_EDIT->value,
        P::LEADS_DELETE->value,
        P::LEADS_ASSIGN->value,
        P::LEADS_EXPORT->value,
        // CRM Customers: Full
        P::CUSTOMERS_VIEW_ALL->value,
        P::CUSTOMERS_CREATE->value,
        P::CUSTOMERS_EDIT->value,
        P::CUSTOMERS_DELETE->value,
        P::CUSTOMERS_EXPORT->value,
        // Sales AI: Full (view + use)
        P::SALES_AI_VIEW->value,
        P::SALES_AI_USE->value,
        // Tasks: Full
        P::TASKS_VIEW_ALL->value,
        P::TASKS_CREATE->value,
        P::TASKS_EDIT->value,
        P::TASKS_ASSIGN->value,
        P::TASKS_CLOSE->value,
        // SOP: Approve/View — KHÔNG có create/edit
        P::SOP_VIEW->value,
        P::SOP_APPROVE->value,
        // Workflow: Monitor only — KHÔNG có edit
        P::WORKFLOW_MONITOR->value,
        // Prompt: View only
        P::PROMPT_VIEW->value,
        // AI Logs: View summary
        P::AI_LOGS_VIEW->value,
        // AI Copilot: Use + View usage
        P::AI_COPILOT_USE->value,
        P::AI_COPILOT_VIEW_USAGE->value,
        // Users: View
        P::USERS_VIEW->value,
        // Reports: Full
        P::REPORTS_FULL->value,
        // Assessment: View + Results
        P::ASSESSMENT_VIEW->value,
        P::ASSESSMENT_RESULTS->value,
        // Job Posting: Full
        P::JOB_POSTING_VIEW->value,
        P::JOB_POSTING_CREATE->value,
        P::JOB_POSTING_EDIT->value,
        P::JOB_POSTING_DELETE->value,
        P::JOB_POSTING_PUBLISH->value,
        // Recruitment: View
        P::RECRUITMENT_VIEW->value,
        // Marketplace: View
        P::MARKETPLACE_VIEW->value,
        // OCOP Product: Full (đăng ký + quản lý sản phẩm OCOP của tổ chức)
        P::OCOP_PRODUCT_VIEW->value,
        P::OCOP_PRODUCT_MANAGE->value,
        // OCOP Scoring: Full (luyện tập + tự đánh giá — văn bản chuẩn bị hồ sơ pháp lý)
        P::OCOP_PRACTICE_USE->value,
        P::OCOP_SELF_ASSESS_USE->value,
        // Business Solution: View (biết tổ chức có thể kích hoạt Solution nào)
        P::SOLUTION_CATALOG_VIEW->value,
        // Business Blueprint: View (xem thư viện Blueprint)
        P::BLUEPRINT_VIEW->value,
        // Organization Solution: Full (chủ tổ chức tự kích hoạt/cấu hình/tạm ngưng)
        P::SOLUTION_ACTIVATE->value,
        P::SOLUTION_CONFIGURE->value,
        P::SOLUTION_SUSPEND->value,
        P::SOLUTION_ARCHIVE->value,
        // Deployment Engine: Full (chạy deploy + xem log)
        P::DEPLOYMENT_RUN->value,
        P::DEPLOYMENT_VIEW_LOGS->value,
    ],

    R::SALES->value => [
        // CRM Leads: Assigned (view + edit chỉ của mình — Policy xử lý)
        P::LEADS_VIEW_ASSIGNED->value,
        P::LEADS_CREATE->value,
        P::LEADS_EDIT->value,       // Policy: chỉ edit lead assigned_to === user->id
        // CRM Customers: Assigned
        P::CUSTOMERS_VIEW_ASSIGNED->value,
        P::CUSTOMERS_CREATE->value,
        P::CUSTOMERS_EDIT->value,
        // Sales AI: Use (dùng output, không config)
        P::SALES_AI_USE->value,
        // AI Copilot: Use
        P::AI_COPILOT_USE->value,
        // Tasks: Assigned
        P::TASKS_VIEW_ASSIGNED->value,
        P::TASKS_CREATE->value,
        // SOP: View related (dept liên quan)
        P::SOP_VIEW_RELATED->value,
        // Workflow: Limited (visibility=public)
        P::WORKFLOW_VIEW_LIMITED->value,
        // Reports: Personal + Team
        P::REPORTS_PERSONAL->value,
        P::REPORTS_TEAM->value,
        // Business Solution: View
        P::SOLUTION_CATALOG_VIEW->value,
        // Business Blueprint: View
        P::BLUEPRINT_VIEW->value,
    ],

    R::OPS->value => [
        // Subscription: View + Billing
        P::SUBSCRIPTION_VIEW->value,
        P::SUBSCRIPTION_BILLING->value,
        // CEO Dashboard: Limited (view, không có AI brief, không có approve)
        P::CEO_DASH_VIEW->value,
        // CRM Leads: Limited — view_all + export + manage tags, KHÔNG có edit/delete/assign
        P::LEADS_VIEW_ALL->value,
        P::LEADS_EXPORT->value,
        P::LEADS_MANAGE_TAGS->value,
        // CRM Customers: View + Create + Edit + Export
        P::CUSTOMERS_VIEW_ALL->value,
        P::CUSTOMERS_CREATE->value,
        P::CUSTOMERS_EDIT->value,
        P::CUSTOMERS_EXPORT->value,
        // Tasks: Full team (view all + full CRUD + assign)
        P::TASKS_VIEW_ALL->value,
        P::TASKS_CREATE->value,
        P::TASKS_EDIT->value,
        P::TASKS_ASSIGN->value,
        P::TASKS_CLOSE->value,
        // SOP: Create/Edit
        P::SOP_VIEW->value,
        P::SOP_CREATE->value,
        P::SOP_EDIT->value,
        // Workflow: Monitor/Edit
        P::WORKFLOW_MONITOR->value,
        P::WORKFLOW_EDIT->value,
        // AI Logs: Limited (view, không full)
        P::AI_LOGS_VIEW->value,
        // AI Copilot: Use + View usage
        P::AI_COPILOT_USE->value,
        P::AI_COPILOT_VIEW_USAGE->value,
        // Reports: Operations scope
        P::REPORTS_OPS->value,
        // Assessment: View + Results
        P::ASSESSMENT_VIEW->value,
        P::ASSESSMENT_RESULTS->value,
        // Job Posting: View only
        P::JOB_POSTING_VIEW->value,
        // OCOP Product: Full
        P::OCOP_PRODUCT_VIEW->value,
        P::OCOP_PRODUCT_MANAGE->value,
        // OCOP Scoring: Practice only (self-assessment dành riêng CEO — trách nhiệm hồ sơ pháp lý)
        P::OCOP_PRACTICE_USE->value,
        // Business Solution: View
        P::SOLUTION_CATALOG_VIEW->value,
        // Business Blueprint: View
        P::BLUEPRINT_VIEW->value,
    ],

    R::MARKETING->value => [
        // CRM Leads: SOURCE VIEW — KHÔNG phải Limited
        // Chỉ xem lead có lead_source, ẩn phone/email
        P::LEADS_VIEW_SOURCE->value,
        // CRM Customers: View only
        P::CUSTOMERS_VIEW_ALL->value,
        // Sales AI: Limited (view, không use/config)
        P::SALES_AI_VIEW->value,
        // Tasks: Limited (visibility=public only)
        P::TASKS_VIEW_LIMITED->value,
        // SOP: View related
        P::SOP_VIEW_RELATED->value,
        // Workflow: Limited
        P::WORKFLOW_VIEW_LIMITED->value,
        // AI Copilot: Use
        P::AI_COPILOT_USE->value,
        // Reports: Marketing scope
        P::REPORTS_MARKETING->value,
        // Marketplace: View (Marketing monitors public listings)
        P::MARKETPLACE_VIEW->value,
        // Business Solution: View
        P::SOLUTION_CATALOG_VIEW->value,
        // Business Blueprint: View
        P::BLUEPRINT_VIEW->value,
    ],

    R::HR->value => [
        // Tasks: HR tasks (dept=hr only)
        P::TASKS_VIEW_DEPT->value,
        P::TASKS_CREATE->value,
        // SOP: Create HR SOP (dept=hr only)
        P::SOP_VIEW->value,
        P::SOP_CREATE_HR->value,
        // Workflow: Limited
        P::WORKFLOW_VIEW_LIMITED->value,
        // AI Copilot: Use
        P::AI_COPILOT_USE->value,
        // Users: Limited (tạo user nội bộ, onboarding)
        P::USERS_HR->value,
        // Reports: HR scope
        P::REPORTS_HR->value,
        // Job Posting: Full (HR manages hiring)
        P::JOB_POSTING_VIEW->value,
        P::JOB_POSTING_CREATE->value,
        P::JOB_POSTING_EDIT->value,
        P::JOB_POSTING_PUBLISH->value,
        // Recruitment: Full (HR Admin = manage, HR = edit+create)
        P::RECRUITMENT_VIEW->value,
        P::RECRUITMENT_CREATE->value,
        P::RECRUITMENT_EDIT->value,
        P::RECRUITMENT_MANAGE->value,
        // Marketplace: Full (HR manages listings + reviews applicants)
        P::MARKETPLACE_VIEW->value,
        P::MARKETPLACE_CREATE->value,
        P::MARKETPLACE_EDIT->value,
        // OCOP Scoring: Practice only — nhân viên luyện tập, không sửa sản phẩm
        P::OCOP_PRACTICE_USE->value,
        // Business Solution: View
        P::SOLUTION_CATALOG_VIEW->value,
        // Business Blueprint: View
        P::BLUEPRINT_VIEW->value,
    ],

    R::AI_OP->value => [
        // CEO Dashboard: Limited
        P::CEO_DASH_VIEW->value,
        // CRM Leads: Limited (view, không edit)
        P::LEADS_VIEW_ALL->value,
        // CRM Customers: View only
        P::CUSTOMERS_VIEW_ALL->value,
        // Sales AI: Config prompt
        P::SALES_AI_CONFIG_PROMPT->value,
        // Tasks: Limited
        P::TASKS_VIEW_LIMITED->value,
        // SOP: AI config (cấu hình AI flow trong SOP)
        P::SOP_AI_CONFIG->value,
        // Workflow: Monitor + AI config
        P::WORKFLOW_MONITOR->value,
        P::WORKFLOW_AI_CONFIG->value,
        // Prompt Management: Full
        P::PROMPT_FULL->value,
        // AI Logs: Full
        P::AI_LOGS_FULL->value,
        // AI Copilot: Use + Config + View usage
        P::AI_COPILOT_USE->value,
        P::AI_COPILOT_CONFIG->value,
        P::AI_COPILOT_VIEW_USAGE->value,
        // Reports: AI usage scope
        P::REPORTS_AI_USAGE->value,
        // Assessment: Config + Reprocess
        P::ASSESSMENT_VIEW->value,
        P::ASSESSMENT_CONFIG->value,
        P::ASSESSMENT_RESULTS->value,
        P::ASSESSMENT_REPROCESS->value,
        // Business Solution: View
        P::SOLUTION_CATALOG_VIEW->value,
        // Business Blueprint: View
        P::BLUEPRINT_VIEW->value,
    ],

    R::ADMIN->value => [
        // Subscription: Full admin
        P::SUBSCRIPTION_VIEW->value,
        P::SUBSCRIPTION_MANAGE->value,
        P::SUBSCRIPTION_BILLING->value,
        P::SUBSCRIPTION_ADMIN->value,
        // Config trên tất cả module (độc lập với data)
        P::CEO_DASH_CONFIG->value,
        P::LEADS_CONFIG->value,
        P::LEADS_VIEW_ALL->value,          // Để support — activity log ghi lại
        P::LEADS_MANAGE_PIPELINE->value,   // Quản lý pipeline stages
        P::LEADS_MANAGE_SOURCES->value,    // Quản lý lead sources
        P::LEADS_MANAGE_TAGS->value,       // Quản lý tags
        // CRM Customers: Config (custom fields) + View
        P::CUSTOMERS_CONFIG->value,
        P::CUSTOMERS_VIEW_ALL->value,
        P::SALES_AI_CONFIG->value,
        P::TASKS_CONFIG->value,
        P::SOP_CONFIG->value,
        P::WORKFLOW_MONITOR->value,
        P::WORKFLOW_EDIT->value,
        P::WORKFLOW_FULL_CONFIG->value,
        P::PROMPT_ADMIN_CONFIG->value,
        P::AI_LOGS_FULL->value,
        // AI Copilot: Use + Config + View usage
        P::AI_COPILOT_USE->value,
        P::AI_COPILOT_CONFIG->value,
        P::AI_COPILOT_VIEW_USAGE->value,
        // User & roles management
        P::USERS_MANAGE->value,
        P::ROLES_MANAGE->value,
        // System
        P::INTEGRATION_MANAGE->value,
        P::AUDIT_VIEW->value,
        P::SYSTEM_CONFIG->value,
        P::REPORTS_FULL->value,
        // Assessment: Full config
        P::ASSESSMENT_VIEW->value,
        P::ASSESSMENT_CONFIG->value,
        P::ASSESSMENT_RESULTS->value,
        P::ASSESSMENT_REPROCESS->value,
        // Job Posting: Manage (system admin)
        P::JOB_POSTING_VIEW->value,
        P::JOB_POSTING_MANAGE->value,
        // Recruitment: Full manage
        P::RECRUITMENT_VIEW->value,
        P::RECRUITMENT_CREATE->value,
        P::RECRUITMENT_EDIT->value,
        P::RECRUITMENT_MANAGE->value,
        // Marketplace: Full manage (approve orgs, global view)
        P::MARKETPLACE_VIEW->value,
        P::MARKETPLACE_CREATE->value,
        P::MARKETPLACE_EDIT->value,
        P::MARKETPLACE_MANAGE->value,
        // Vertical templates: Full manage (dashboard/vertical-templates — thư viện mẫu)
        P::VERTICAL_TEMPLATES_MANAGE->value,
        // OCOP Rubric: Full manage (danh mục 26 Bộ sản phẩm + cây tiêu chí — QĐ 26/2026/QĐ-TTg)
        P::OCOP_RUBRIC_MANAGE->value,
        // Business Solution: Full manage (tạo/sửa/publish/archive danh mục) + View
        P::SOLUTION_CATALOG_MANAGE->value,
        P::SOLUTION_CATALOG_VIEW->value,
        // Business Blueprint: Full manage (Business Analyst/Product Owner chưa có role riêng — xem PermissionEnum)
        P::BLUEPRINT_VIEW->value,
        P::BLUEPRINT_CREATE->value,
        P::BLUEPRINT_EDIT->value,
        P::BLUEPRINT_DELETE->value,
        P::BLUEPRINT_PUBLISH->value,
        P::BLUEPRINT_ARCHIVE->value,
        P::BLUEPRINT_CLONE->value,
        // Organization Solution: Full (hỗ trợ tổ chức kích hoạt/cấu hình khi cần)
        P::SOLUTION_ACTIVATE->value,
        P::SOLUTION_CONFIGURE->value,
        P::SOLUTION_SUSPEND->value,
        P::SOLUTION_ARCHIVE->value,
        // Deployment Engine: Full (hỗ trợ tổ chức chạy deploy khi cần)
        P::DEPLOYMENT_RUN->value,
        P::DEPLOYMENT_VIEW_LOGS->value,
    ],

    R::VIEWER->value => [
        // CEO Dashboard: View limited
        P::CEO_DASH_VIEW->value,
        // Tasks: View limited (public only)
        P::TASKS_VIEW_LIMITED->value,
        // SOP: View limited
        P::SOP_VIEW->value,
        // Reports: Shared only
        P::REPORTS_SHARED->value,
        // OCOP Product: View only
        P::OCOP_PRODUCT_VIEW->value,
        // Business Solution: View
        P::SOLUTION_CATALOG_VIEW->value,
        // Business Blueprint: View
        P::BLUEPRINT_VIEW->value,
        // CRM Leads = No, Workflow = No, Prompt = No
    ],
];