<?php

return [

    'subscription_slug' => 'main',
    'default_plan'      => env('SUBSCRIPTION_DEFAULT_PLAN', 'starter'),

    // Chỉ 4 module THẬT SỰ đang được `feature:module.x` middleware gate — xem
    // `Modules/Lead|Customer|Assessment|WorkflowAutomation/routes/web.php`. Trước đây có thêm
    // 'crm'/'sop'/'hr'/'recruitment'/'project'/'kc'/'marketplace'/'ai' nhưng không module nào
    // tên như vậy được gate bởi feature này trong codebase.
    'module_features' => [
        'lead'       => 'module.lead',
        'customer'   => 'module.customer',
        'workflow'   => 'module.workflow',
        'assessment' => 'module.assessment',
    ],

    'limit_models' => [
        'limit.members'   => \App\Models\User::class,
    ],

    // `limit.members` là limit DUY NHẤT đang thực sự được enforce (đếm User::where('organization_id',...)
    // rồi so sánh — xem `Modules/User/app/Http/Controllers/UserController.php`). Trước đây còn có
    // 'limit.employees' (trùng khái niệm, không có mapping/enforce riêng), 'limit.workflows',
    // 'limit.projects', 'limit.storage_gb', 'limit.ai_agents' — hoặc không tồn tại khái niệm
    // tương ứng nào (projects/ai_agents), hoặc có hạ tầng thật (Workflow, Media storage) nhưng
    // chưa từng được dây nối vào Subscription để enforce — đã bỏ để tránh gói "hứa" giới hạn
    // không có tác dụng gì trên thực tế.
    'limit_labels' => [
        'limit.members' => 'Người dùng',
    ],

    'on_expire'             => 'restrict',
    'renewal_reminder_days' => [7, 3, 1],
    'currency'              => env('SUBSCRIPTION_CURRENCY', 'VND'),

    'gateways' => [
        'default' => env('PAYMENT_GATEWAY', 'manual'),

        'vnpay' => [
            'tmn_code' => env('VNPAY_TMN_CODE'),
            'secret'   => env('VNPAY_SECRET'),
            'url'      => env('VNPAY_URL', 'https://sandbox.vnpayment.vn/paymentv2/vpcpay.html'),
        ],

        // SePay — bank transfer monitoring. Docs: https://docs.sepay.vn
        'sepay' => [
            'api_key'        => env('SEPAY_API_KEY'),        // API key from SePay dashboard
            'account_number' => env('SEPAY_ACCOUNT_NUMBER'), // Bank account number to display
            'bank_name'      => env('SEPAY_BANK_NAME', 'MB Bank'),
            'account_name'   => env('SEPAY_ACCOUNT_NAME'),   // Account holder name
        ],

        // Manual gateway — admin-only, local/testing by default.
        // Set SUBSCRIPTION_MANUAL_GATEWAY_ENABLED=true in production only for trusted admin use.
        // Set SUBSCRIPTION_MANUAL_GATEWAY_SECRET to require a shared secret on the webhook endpoint.
        'manual' => [
            'enabled' => env('SUBSCRIPTION_MANUAL_GATEWAY_ENABLED', false),
            'secret'  => env('SUBSCRIPTION_MANUAL_GATEWAY_SECRET'),
        ],
    ],

];
