<?php

namespace Modules\Newsletter\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Modules\Newsletter\Listeners\SyncSubscriberFromResendWebhookListener;
use Resend\Laravel\Events\ContactDeleted;
use Resend\Laravel\Events\ContactUpdated;
use Resend\Laravel\Events\EmailBounced;
use Resend\Laravel\Events\EmailComplained;

/**
 * spec/Newsletter_Technical_Specification.md §9.3 — lắng nghe Event có sẵn từ
 * resend/resend-laravel (route webhook + xác thực chữ ký đã có sẵn trong package, §2.1).
 */
class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        ContactUpdated::class   => [[SyncSubscriberFromResendWebhookListener::class, 'handleContactUpdated']],
        ContactDeleted::class   => [[SyncSubscriberFromResendWebhookListener::class, 'handleContactDeleted']],
        EmailBounced::class     => [[SyncSubscriberFromResendWebhookListener::class, 'handleEmailBounced']],
        EmailComplained::class  => [[SyncSubscriberFromResendWebhookListener::class, 'handleEmailComplained']],
    ];

    protected static $shouldDiscoverEvents = false;
}
