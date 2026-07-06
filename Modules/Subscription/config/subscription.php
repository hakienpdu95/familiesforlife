<?php

return [

    'subscription_slug' => 'main',
    'default_plan'      => env('SUBSCRIPTION_DEFAULT_PLAN', 'starter'),

    'module_features' => [
        'crm'         => 'module.crm',
        'workflow'    => 'module.workflow',
        'sop'         => 'module.sop',
        'hr'          => 'module.hr',
        'recruitment' => 'module.recruitment',
        'assessment'  => 'module.assessment',
        'project'     => 'module.project',
        'kc'          => 'module.kc',
        'marketplace' => 'module.marketplace',
        'ai'          => 'module.ai',
    ],

    'limit_models' => [
        'limit.employees' => \Modules\Employee\Models\Employee::class,
        'limit.members'   => \App\Models\User::class,
    ],

    'limit_labels' => [
        'limit.employees'  => 'Nhân viên',
        'limit.members'    => 'Người dùng',
        'limit.workflows'  => 'Workflow',
        'limit.projects'   => 'Dự án',
        'limit.storage_gb' => 'Dung lượng (GB)',
        'limit.ai_agents'  => 'AI Agents',
    ],

    'quota_slugs' => [
        'quota.ai_requests',
        'quota.ai_tokens',
        'quota.workflow_runs',
        'quota.email_notifications',
    ],

    'quota_labels' => [
        'quota.ai_requests'         => 'AI requests / tháng',
        'quota.ai_tokens'           => 'AI tokens / tháng',
        'quota.workflow_runs'       => 'Workflow executions / tháng',
        'quota.email_notifications' => 'Email notifications / tháng',
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
