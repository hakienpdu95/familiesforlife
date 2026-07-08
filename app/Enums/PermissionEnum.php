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

    // ══ TASKS ══════════════════════════════════════════════════════
    // CEO=Full | Sales=Assigned | Ops=Full team | Marketing=Limited | HR=HR tasks | AI_OP=Limited | Admin=Config | Viewer=View limited
    case TASKS_VIEW_ALL      = 'tasks.view_all';      // Ops (full team scope)
    case TASKS_VIEW_ASSIGNED = 'tasks.view_assigned'; // Sales
    case TASKS_VIEW_DEPT     = 'tasks.view_dept';     // HR (HR tasks — dept=hr)
    case TASKS_VIEW_LIMITED  = 'tasks.view_limited';  // Marketing, AI_OP, Viewer (visibility=public)
    case TASKS_CREATE        = 'tasks.create';        // CEO, Sales, Ops, HR
    case TASKS_EDIT          = 'tasks.edit';          // CEO, Ops
    case TASKS_ASSIGN        = 'tasks.assign';        // CEO, Ops
    case TASKS_CLOSE         = 'tasks.close';         // CEO, Ops
    case TASKS_CONFIG        = 'tasks.config';        // System Admin

    // ══ SOP ════════════════════════════════════════════════════════
    // CEO=Approve/View | Sales=View related | Ops=Create/Edit | Marketing=View related
    // HR=Create HR SOP | AI_OP=AI config | Admin=Config | Viewer=View limited
    case SOP_VIEW         = 'sop.view';         // CEO, Viewer (view limited)
    case SOP_APPROVE      = 'sop.approve';      // CEO
    case SOP_VIEW_RELATED = 'sop.view_related'; // Sales, Marketing (dept liên quan)
    case SOP_CREATE       = 'sop.create';       // Ops
    case SOP_EDIT         = 'sop.edit';         // Ops
    case SOP_CREATE_HR    = 'sop.create_hr';    // HR (chỉ dept=hr)
    case SOP_AI_CONFIG    = 'sop.ai_config';    // AI Operator (cấu hình AI trong SOP flow)
    case SOP_CONFIG       = 'sop.config';       // System Admin

    // ══ WORKFLOW ═══════════════════════════════════════════════════
    // CEO=Monitor | Sales=Limited | Ops=Monitor/Edit | Marketing=Limited | HR=Limited
    // AI_OP=AI config | Admin=Full config
    case WORKFLOW_MONITOR      = 'workflow.monitor';      // CEO, Ops
    case WORKFLOW_EDIT         = 'workflow.edit';         // Ops (Monitor/Edit = monitor+edit)
    case WORKFLOW_VIEW_LIMITED = 'workflow.view_limited'; // Sales, Marketing, HR
    case WORKFLOW_AI_CONFIG    = 'workflow.ai_config';    // AI Operator
    case WORKFLOW_FULL_CONFIG  = 'workflow.full_config';  // System Admin

    // ══ PROMPT MANAGEMENT ══════════════════════════════════════════
    // CEO=View | AI_OP=Full | Admin=Admin config
    case PROMPT_VIEW         = 'prompt.view';         // CEO (read-only)
    case PROMPT_FULL         = 'prompt.full';         // AI Operator
    case PROMPT_ADMIN_CONFIG = 'prompt.admin_config'; // System Admin

    // ══ AI LOGS ════════════════════════════════════════════════════
    // CEO=View summary | Ops=Limited | AI_OP=Full | Admin=Full
    case AI_LOGS_FULL    = 'ai_logs.full';    // AI Operator, Admin
    case AI_LOGS_VIEW    = 'ai_logs.view';    // CEO(summary), Ops(limited)

    // ══ AI COPILOT ═════════════════════════════════════════════════
    // CEO=Use+ViewUsage | Sales=Use | Ops=Use | HR=Use | Marketing=Use | AI_OP=Use+Config | Admin=Use+Config
    case AI_COPILOT_USE        = 'ai_copilot.use';        // Sử dụng AI task execution
    case AI_COPILOT_CONFIG     = 'ai_copilot.config';     // Quản lý agents + prompts
    case AI_COPILOT_VIEW_USAGE = 'ai_copilot.view_usage'; // Xem usage stats / logs

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
    case REPORTS_AI_USAGE  = 'reports.ai_usage';  // AI Operator
    case REPORTS_SHARED    = 'reports.shared';    // Viewer

    // ══ ASSESSMENT (Chấm điểm khảo sát) ═══════════════════════════
    // CEO=View | Ops=View | AI_OP=Config+Reprocess | Admin=Full
    case ASSESSMENT_VIEW      = 'assessment.view';      // CEO, Ops, AI_OP — xem danh sách assessments
    case ASSESSMENT_CONFIG    = 'assessment.config';    // AI_OP, Admin — wizard cấu hình
    case ASSESSMENT_RESULTS   = 'assessment.results';   // CEO, Ops, AI_OP — xem kết quả
    case ASSESSMENT_REPROCESS = 'assessment.reprocess'; // AI_OP, Admin — force recalculate

    // ══ JOB POSTING ════════════════════════════════════════════════
    // CEO=Full | HR=Full | Ops=View | Admin=Config | Viewer=No
    case JOB_POSTING_VIEW    = 'job_posting.view';    // CEO, HR, Ops
    case JOB_POSTING_CREATE  = 'job_posting.create';  // CEO, HR
    case JOB_POSTING_EDIT    = 'job_posting.edit';    // CEO, HR
    case JOB_POSTING_DELETE  = 'job_posting.delete';  // CEO only
    case JOB_POSTING_PUBLISH = 'job_posting.publish'; // CEO, HR
    case JOB_POSTING_MANAGE  = 'job_posting.manage';  // System Admin

    // ══ RECRUITMENT (ATS) ══════════════════════════════════════════
    // HR_Admin=Full | Recruiter=Process | Hiring_Manager=View own | Interviewer=View+Eval
    case RECRUITMENT_VIEW    = 'recruitment.view';    // All HR roles, Hiring Manager, Interviewer
    case RECRUITMENT_CREATE  = 'recruitment.create';  // HR Admin, Recruiter
    case RECRUITMENT_EDIT    = 'recruitment.edit';    // HR Admin, Recruiter
    case RECRUITMENT_MANAGE  = 'recruitment.manage';  // HR Admin — config pipeline, delete

    // ══ MARKETPLACE CENTER ═════════════════════════════════════════
    // CEO=View | HR=Full | Marketing=View | Admin=Manage (approve orgs, global)
    case MARKETPLACE_VIEW   = 'marketplace.view';   // CEO, HR, Marketing — xem listings của org
    case MARKETPLACE_CREATE = 'marketplace.create'; // HR — tạo listing mới
    case MARKETPLACE_EDIT   = 'marketplace.edit';   // HR — sửa/đóng listing
    case MARKETPLACE_MANAGE = 'marketplace.manage'; // System Admin — duyệt org, xem tất cả

    // ══ SUBSCRIPTION ═══════════════════════════════════════════════
    case SUBSCRIPTION_VIEW    = 'subscription.view';
    case SUBSCRIPTION_MANAGE  = 'subscription.manage';
    case SUBSCRIPTION_BILLING = 'subscription.billing';
    case SUBSCRIPTION_ADMIN   = 'subscription.admin';

    // ══ SYSTEM ═════════════════════════════════════════════════════
    case INTEGRATION_MANAGE = 'integration.manage';
    case AUDIT_VIEW         = 'audit.view';
    case SYSTEM_CONFIG      = 'system.config';

    // ══ VERTICAL TEMPLATES ═════════════════════════════════════════
    // Không còn admin UI (dashboard/vertical-templates đã xoá) — case này chỉ còn được
    // VerticalTemplatePolicy tham chiếu cho nhánh library-owned (organization_id = null),
    // nhánh org-owned dùng $user->can('update', $organization) là chính.
    case VERTICAL_TEMPLATES_MANAGE = 'vertical_templates.manage';

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
    case POST_ARTICLE_VIEW    = 'post_article.view';
    case POST_ARTICLE_CREATE  = 'post_article.create';
    case POST_ARTICLE_EDIT    = 'post_article.edit';
    case POST_ARTICLE_DELETE  = 'post_article.delete';
    case POST_ARTICLE_PUBLISH = 'post_article.publish';
}