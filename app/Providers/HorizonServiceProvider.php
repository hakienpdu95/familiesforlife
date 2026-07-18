<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Laravel\Horizon\Horizon;
use Laravel\Horizon\HorizonApplicationServiceProvider;

class HorizonServiceProvider extends HorizonApplicationServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        parent::boot();

        // spec/PostSearch_Meilisearch_Technical_Specification.md §11/§13 bước 6 — bắt buộc
        // có alert khi job hàng đợi fail hẳn (hết `tries`), bao gồm cả job Scout
        // (Laravel\Scout\Jobs\MakeSearchable/RemoveFromSearch, chạy trên queue mặc định vì
        // model không override syncWithSearchUsingQueue()). Chỉ gọi route*NotificationsTo()
        // khi có cấu hình thật ở config('horizon.alerts') — gọi với giá trị rỗng sẽ lỗi.
        if ($mailTo = config('horizon.alerts.mail_to')) {
            Horizon::routeMailNotificationsTo($mailTo);
        }

        if ($slackWebhook = config('horizon.alerts.slack_webhook')) {
            Horizon::routeSlackNotificationsTo($slackWebhook, config('horizon.alerts.slack_channel'));
        }
    }

    /**
     * Register the Horizon gate.
     *
     * This gate determines who can access Horizon in non-local environments.
     */
    protected function gate(): void
    {
        Gate::define('viewHorizon', function ($user = null) {
            return in_array(optional($user)->email, [
                //
            ]);
        });
    }
}
