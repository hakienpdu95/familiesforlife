<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Application Name
    |--------------------------------------------------------------------------
    |
    | This value is the name of your application, which will be used when the
    | framework needs to place the application's name in a notification or
    | other UI elements where an application name needs to be displayed.
    |
    */

    'name' => env('APP_NAME', 'Laravel'),

    /*
    |--------------------------------------------------------------------------
    | Public Site Name
    |--------------------------------------------------------------------------
    |
    | Tên hiển thị công khai (title tag, footer, Organization/Article JSON-LD) — tách khỏi
    | `app.name` (dùng cho notification/UI nội bộ) vì .env thường để nguyên APP_NAME=Laravel
    | mặc định của Laravel skeleton, không phản ánh tên site thật. Trước đây 3 nơi khác nhau tự
    | lặp lại "nếu app.name === Laravel thì fallback 'Vì Gia Đình'" — gộp về 1 chỗ duy nhất.
    |
    */

    'site_name' => (env('APP_NAME') && env('APP_NAME') !== 'Laravel') ? env('APP_NAME') : 'Vì Gia Đình',

    /*
    |--------------------------------------------------------------------------
    | Site-wide Organization/WebSite JSON-LD (GEO/AEO)
    |--------------------------------------------------------------------------
    |
    | Dùng để dựng node Organization/WebSite trong resources/views/layouts/frontend.blade.php
    | — khác `site_name` (đã dùng riêng cho title tag + publisher trong Article schema), 2 field
    | này CHƯA có nguồn nào để đọc trước đây, khiến AI/search engine không có cách nào xác nhận
    | logo/mạng xã hội chính thức của site (entity clarity — spec/blog.md nhiều nguồn cùng nhấn
    | mạnh điểm này). Để trống nếu chưa có — schema tự bỏ qua field rỗng thay vì bịa giá trị giả.
    |
    */

    'site_logo_url' => env('APP_SITE_LOGO_URL'),

    // Phân tách bằng dấu phẩy trong .env, VD: APP_SITE_SOCIAL_LINKS="https://facebook.com/...,https://youtube.com/..."
    'site_social_links' => array_filter(explode(',', (string) env('APP_SITE_SOCIAL_LINKS', ''))),

    // Trước đây hardcode riêng trong Modules/Post/resources/views/public/home.blade.php — gộp về
    // đây để Organization JSON-LD site-wide (layouts/frontend.blade.php) dùng chung 1 nguồn.
    'site_description' => env('APP_SITE_DESCRIPTION', 'Cẩm nang gia đình — hoạt động, trường học, nuôi dạy con và trải nghiệm cho cả nhà.'),

    /*
    |--------------------------------------------------------------------------
    | Application Environment
    |--------------------------------------------------------------------------
    |
    | This value determines the "environment" your application is currently
    | running in. This may determine how you prefer to configure various
    | services the application utilizes. Set this in your ".env" file.
    |
    */

    'env' => env('APP_ENV', 'production'),

    /*
    |--------------------------------------------------------------------------
    | Application Debug Mode
    |--------------------------------------------------------------------------
    |
    | When your application is in debug mode, detailed error messages with
    | stack traces will be shown on every error that occurs within your
    | application. If disabled, a simple generic error page is shown.
    |
    */

    'debug' => (bool) env('APP_DEBUG', false),

    /*
    |--------------------------------------------------------------------------
    | Application URL
    |--------------------------------------------------------------------------
    |
    | This URL is used by the console to properly generate URLs when using
    | the Artisan command line tool. You should set this to the root of
    | the application so that it's available within Artisan commands.
    |
    */

    'url' => env('APP_URL', 'http://localhost'),

    /*
    |--------------------------------------------------------------------------
    | Application Timezone
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default timezone for your application, which
    | will be used by the PHP date and date-time functions. The timezone
    | is set to "UTC" by default as it is suitable for most use cases.
    |
    */

    'timezone' => 'UTC',

    /*
    |--------------------------------------------------------------------------
    | Application Locale Configuration
    |--------------------------------------------------------------------------
    |
    | The application locale determines the default locale that will be used
    | by Laravel's translation / localization methods. This option can be
    | set to any locale for which you plan to have translation strings.
    |
    */

    'locale' => env('APP_LOCALE', 'en'),

    'fallback_locale' => env('APP_FALLBACK_LOCALE', 'en'),

    'faker_locale' => env('APP_FAKER_LOCALE', 'en_US'),

    /*
    |--------------------------------------------------------------------------
    | Encryption Key
    |--------------------------------------------------------------------------
    |
    | This key is utilized by Laravel's encryption services and should be set
    | to a random, 32 character string to ensure that all encrypted values
    | are secure. You should do this prior to deploying the application.
    |
    */

    'cipher' => 'AES-256-CBC',

    'key' => env('APP_KEY'),

    'previous_keys' => [
        ...array_filter(
            explode(',', (string) env('APP_PREVIOUS_KEYS', ''))
        ),
    ],

    /*
    |--------------------------------------------------------------------------
    | Maintenance Mode Driver
    |--------------------------------------------------------------------------
    |
    | These configuration options determine the driver used to determine and
    | manage Laravel's "maintenance mode" status. The "cache" driver will
    | allow maintenance mode to be controlled across multiple machines.
    |
    | Supported drivers: "file", "cache"
    |
    */

    'maintenance' => [
        'driver' => env('APP_MAINTENANCE_DRIVER', 'file'),
        'store' => env('APP_MAINTENANCE_STORE', 'database'),
    ],

];
