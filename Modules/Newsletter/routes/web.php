<?php

use Illuminate\Support\Facades\Route;
use Modules\Newsletter\Features\BroadcastSending\Http\BroadcastAdminController;
use Modules\Newsletter\Features\PublicSubscription\Http\PublicSubscriptionController;
use Modules\Newsletter\Features\SubscriberManagement\Http\SubscriberAdminController;

// spec/Newsletter_Technical_Specification.md §10 — công khai, không auth.
Route::post('newsletter/subscribe', [PublicSubscriptionController::class, 'subscribe'])
    ->middleware('throttle:10,1')->name('newsletter.public.subscribe');

// Chỉ có ý nghĩa khi NEWSLETTER_DOUBLE_OPT_IN=true (§0 mục 14) — vẫn đăng ký route kể cả khi
// tắt, để bật/tắt chỉ cần đổi .env, không cần đổi route.
Route::get('newsletter/confirm/{subscriber}', [PublicSubscriptionController::class, 'confirm'])
    ->middleware('signed')->name('newsletter.public.confirm');

// Admin — platform-wide, không tenant (§0 mục 1).
Route::middleware(['auth'])->prefix('dashboard/newsletter')->name('backend.newsletter.')->group(function () {
    Route::get('subscribers', [SubscriberAdminController::class, 'index'])->name('subscribers.index');
    Route::delete('subscribers/{subscriber}', [SubscriberAdminController::class, 'destroy'])->name('subscribers.destroy');

    // 'broadcast/logs' phải đăng ký TRƯỚC nếu có route {broadcast} wildcard — hiện không có,
    // nhưng giữ đúng thứ tự path tường minh trước để nhất quán với pattern đã dùng trong app
    // (vd Modules/Post 'articles/pending-review' trước Route::resource('articles', ...)).
    Route::get('broadcast', [BroadcastAdminController::class, 'create'])->name('broadcast.create');
    Route::post('broadcast', [BroadcastAdminController::class, 'send'])->name('broadcast.send');
    Route::get('broadcast/logs', [BroadcastAdminController::class, 'logs'])->name('broadcast.logs');
});
