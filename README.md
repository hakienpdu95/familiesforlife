<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework.

In addition, [Laracasts](https://laracasts.com) contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

You can also watch bite-sized lessons with real-world projects on [Laravel Learn](https://laravel.com/learn), where you will be guided through building a Laravel application from scratch while learning PHP fundamentals.

## Agentic Development

Laravel's predictable structure and conventions make it ideal for AI coding agents like Claude Code, Cursor, and GitHub Copilot. Install [Laravel Boost](https://laravel.com/docs/ai) to supercharge your AI workflow:

```bash
composer require laravel/boost --dev

php artisan boost:install
```

Boost provides your agent 15+ tools and skills that help agents build Laravel applications while following best practices.

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).

## Chỉ cần 4 dòng .env:
  OTP_CHANNEL_DRIVER=zbs_zns
  ZBS_APP_ID=your_app_id
  ZBS_APP_SECRET=your_secret
  ZBS_OTP_TEMPLATE_ID=your_template_id
  Sau đó chạy php artisan zbs:token:setup {code} một lần để lấy token ban đầu.

php artisan migration:sync --dry-run
php artisan migration:sync

npx vite build --config vite.config.backend.js

php artisan queue:work --queue=high,default,low,workflows,webhooks,ai,actlog,passport

# Sửa JSON → chạy lệnh này → DB cập nhật đầy đủ
php artisan migration:generate --fresh
php artisan import:provinces-wards
php artisan db:seed

php artisan module:make Auth

To start Reverb in dev:
php artisan reverb:start
Then rebuild assets: npm run build or npm run dev.

Reverb isn't started. Run it:

  php artisan reverb:start

  Keep that terminal open — it's a long-running process (like npm run dev). You'll see:

    INFO  Starting server on 0.0.0.0:8080

  If port 8080 is blocked by a firewall or already taken, start on a different port:

  php artisan reverb:start --port=8081
  # then update REVERB_PORT=8081 in .env
  
# > Đọc file spec/DacTa_CompetencyPassport_Platform.md và làm phase 0 trước, implement theo đúng spec, đọc thêm file docs/module-list-pattern.md để tuân thủ đúng cấu trúc và nguyên tắc chuẩn khi thiết kế và triển khai module theo cấu trúc Advanced Vertical Slice (AVSA) + CQRS-lite + Laravel Modules + Laravel Actions, bổ sung ra mdenu sidebar để dùng, phần views hãy kế thừa và làm chuẩn @extends('layouts.backend')

# > Đọc file docs/thuchoc/02-DAC-TA-THIET-KE-5-MODULE-MOI.md và làm module đầu tiên, implement theo đúng và sát spec đó, tuân thủ quy tắc và nguyên tắc hệ thống hiện tại để build

# > Đọc file docs/form-ui-spec.md và implement theo đúng spec đó, áp dụng spec này vào module Customer, review và tối ưu UI/UX form chuẩn để hành vi người dùng dễ thao tác và sử dụng linh hoạt, thuận tiện

Notification Thông báo in-app  + push — (thông báo trên chuông) tất cả module đều cần

Đọc file docs/form-ui-spec.md và implement theo đúng spec đó, áp dụng spec này vào module AiCopilot

# > Thêm org selector theo pattern _resolveOrganizations() vào form create/edit của  module X

# > Áp dụng pattern org selector của career-pathway-admin create vào form [tên module].