# Auth Module Specification

**Module**: `Modules/Auth`

**Architecture**: Advanced Vertical Slice (AVSA) + CQRS-lite + Laravel Modules + Laravel Actions

**Tech**:
- Laravel Fortify (web auth)
- Laravel Sanctum (chỉ dùng cho web auth)
- Spatie Laravel Permission (RBAC)
- Spatie Laravel Data (DTO)
- Lorisleiva Laravel Actions

**Mục tiêu chính**: Xây dựng hệ thống xác thực web hoàn chỉnh cho SaaS CRM backend.

## 1. Responsibilities
- User registration, login, logout
- Role & Permission assignment (RBAC)
- Fortify customization
- Tạo sẵn **2 tài khoản Administrator full quyền**:
  - `super-admin@system.local` (quyền tối cao)
  - `admin@system.local` (quyền quản trị đầy đủ)

**Lưu ý**: Bỏ hoàn toàn phần Sanctum Personal Access Token (create/revoke/list). Hiện tại chỉ tập trung web authentication.

## 2. Directory Structure (AVSA)
Modules/Auth/
├── Actions/
│   └── Auth/
│       ├── RegisterUserAction.php
│       ├── LoginUserAction.php
│       ├── LogoutUserAction.php
│       ├── ResetPasswordAction.php
│       ├── VerifyEmailAction.php
│       └── UpdateProfileAction.php
├── Data/
│   └── Requests/
│       ├── LoginData.php
│       ├── RegisterData.php
│       ├── ResetPasswordData.php
│       └── ProfileData.php
├── Models/
│   └── User.php
├── Providers/
│   └── AuthServiceProvider.php
├── Routes/
│   ├── web.php
├── Resources/
│   └── views/auth/          # Login, Register, Forgot Password...
├── Database/
│   ├── Seeders/
│   │   └── AuthSeeder.php   # Tạo roles + 2 admin accounts
│   └── Migrations/
└── config.php

## 3. Key Rules Claude Phải Tuân Thủ

- Tất cả logic auth phải dùng **Laravel Actions** (không viết trực tiếp trong Controller)
- Sử dụng **Spatie Data** cho tất cả input/output
- User model phải có `HasRoles`
- Không sửa file gốc của Fortify trừ khi override qua config
- Tất cả route auth phải prefix `/auth` (web)
- Role/Permission phải được seed qua Migration + Seeder trong module
- **Tất cả view auth** phải kế thừa từ master layout backend:
- Sau khi login thành công → redirect về dashboard (không dùng trang mặc định của Fortify)

## 4. Fortify Customization (quan trọng)
- Override các action mặc định của Fortify qua `AuthServiceProvider` hoặc config/fortify.php
- Sử dụng custom `LoginResponse`, `RegisterResponse`, `LogoutResponse`

## 5. Priority Tasks (thực thi theo thứ tự)
1. Tạo Models\User với traits cần thiết
2. Tạo các DTO trong Data/Requests
3. Tạo các Action trong Actions/Auth
4. Cấu hình Fortify trong AuthServiceProvider
5. Tạo Routes + override Fortify views (kế thừa master backend layout)
6. Tạo Migration + AuthSeeder (roles, permissions + 2 admin accounts)
7. Cấu hình redirect sau login → dashboard 

Claude chỉ được tạo/sửa file **bên trong** `Modules/Auth/`. Không được chạm vào app/ trừ khi thật sự cần override.

Bắt đầu bằng việc tạo cấu trúc thư mục và Models\User trước.