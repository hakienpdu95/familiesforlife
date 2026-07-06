# Organization Module Specification

**Module**: `Modules/Organization`

**Architecture**: Advanced Vertical Slice (AVSA) + CQRS-lite + Laravel Modules + Laravel Actions

**Mục tiêu**: Xây dựng nền tảng multi-tenant SaaS (single DB + organization_id). Đây là module quan trọng nhất sau Auth.

**Tech stack**:
- Spatie Laravel Permission (với Teams feature)
- Spatie Laravel Data
- Laravel Actions
- Laravel Subscriptions (gắn subscription với Organization)

## 1. Responsibilities
- Quản lý Organization (tổ chức/công ty)
- User Membership & Invitation
- Role & Permission scoped per Organization (Spatie Teams)
- Current Organization Context
- Organization Settings & Subscription
- Làm nền tảng cho mọi module sau (Lead, Task, SOP, Proposal…)

## 2. Directory Structure (AVSA)

Modules/Organization/
├── Actions/
│   ├── CreateOrganizationAction.php
│   ├── InviteUserToOrganizationAction.php
│   ├── AcceptInvitationAction.php
│   ├── UpdateOrganizationSettingsAction.php
│   └── SwitchOrganizationAction.php
├── Data/
│   ├── Requests/
│   │   ├── CreateOrganizationData.php
│   │   ├── InviteUserData.php
│   │   └── OrganizationSettingsData.php
├── Models/
│   ├── Organization.php
│   └── OrganizationInvitation.php
├── Traits/
│   └── BelongsToOrganization.php          # Trait quan trọng cho các module sau
├── Providers/
│   └── OrganizationServiceProvider.php
├── Routes/
│   ├── web.php
│   └── api.php
├── Middleware/
│   └── SetCurrentOrganization.php
├── Tests/
└── config.php


## 3. Key Rules (Claude PHẢI tuân thủ nghiêm ngặt)

- Mọi model business sau này **bắt buộc** có cột `organization_id` + dùng Trait `BelongsToOrganization`
- Dùng **Spatie Teams** (`team_id = organization_id`) để scope Role/Permission
- Role mặc định:
  - Global: `super-admin`
  - Organization-scoped: `owner`, `admin`, `manager`, `member`- User có thể thuộc nhiều Organization (multi-org support) => tham khảo ày (Database/Seeders/OrganizationRolePermissionSeeder.php)
- User có thể thuộc nhiều Organization (multi-org support)
- Middleware `SetCurrentOrganization` chạy sau auth để set `current_organization_id`
- Tất cả query sau này phải tự động filter theo `current_organization_id` qua Global Scope
- Tối ưu hiệu suất, dễ mở rộng, tuân thủ single responsibility
- Các setting của organization không nên lưu dưới dạng json nhé, nên tạo bảng để lưu cho phù hợp

## 4. Priority Tasks (thực thi theo thứ tự)

1. Tạo Model `Organization` + migration (hãy tham khảo file GenerateMigration.php và GenerateExtension.php - render_extension_file.json trong quá trình tạo file tự động nhé - lưu ý bổ sung thêm các thông tin thuộc về tổ chức DN như mã số thuế, địa chỉ,...)
2. Tạo Membership/Invitation models (`OrganizationUser`, `OrganizationInvitation`)
3. Tích hợp Spatie Permission + Teams
4. Tạo Trait `BelongsToOrganization` + Global Scope
5. Tạo Middleware `SetCurrentOrganization` + helper `CurrentOrganization`
6. Tạo các Action chính
7. Tạo Seeder default roles & permissions cho Organization (`OrganizationRolePermissionSeeder.php`)
8. Cấu hình Organization Settings + link với Subscription

**Sau khi xong Organization module**, các module sau (Lead, Task, SOP…) sẽ cực kỳ dễ làm vì chỉ cần extend trait và thêm `organization_id`.

Bắt đầu ngay bằng việc tạo Model `Organization` và Trait `BelongsToOrganization`.

Claude chỉ được tạo/sửa file bên trong `Modules/Organization/`.