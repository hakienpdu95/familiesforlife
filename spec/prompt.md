Tôi có hệ thống SaaS SME Laravel 13 + Alpine.js với các bảng/module sẵn có sau:

**Bảng/module đã có trong hệ thống:**
- Modules/Organization
- Modules/Assessment
- Modules/Auth
- Modules/Branch
- Modules/Department
- Modules/Employee
- Modules/JobTitle
- Modules/Lead
- Modules/LeadPipelineStage
- Modules/OrgChart
- Modules/PerformanceReview
- Modules/Project
- Modules/Survey
- Modules/User
- Modules/WorkflowAutomation
- Modules/ActivityLog

Read all migration files in database/migrations/ and all model files in app/Models/ and model in all folder Modules/*.

Then read these spec files: spec/job-posting.md & spec/recruitment.md & spec/marketplace.md

Compare and do:
1. List fields/tables in spec that DUPLICATE existing system (organizations, users, roles, departments, employees, job_titles, leaves...)
2. List FK types that are WRONG (UUID vs BIGINT mismatches)
3. List fields in spec that DON'T EXIST in migrations yet
4. Update the spec .md files: remove duplicates, fix FK types, keep only what's new
5. Output updated .md files

**Lưu ý**
Trong thiết kế bảng, tuyệt đối và hạn chế không sử dụng json để lưu data, vì hạn chế trong query, nữa là thiết kế bảng luôn có cột id bigtin và cột uuid nhé
$table->id();
$table->uuid()->nullable()->unique()->comment('Public UUID — expose ra ngoài, không phải PK');