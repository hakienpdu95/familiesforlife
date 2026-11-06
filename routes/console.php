<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use Modules\Auth\Models\SocialAccount;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// KC: auto-archive expired documents
Schedule::command('kc:expire-items')
    ->name('kc:expire-items')
    ->dailyAt('01:00')
    ->onOneServer();

// Media: cleanup Jodit orphan images older than 24h — chạy mỗi 4h
Schedule::command('media:cleanup-orphans')
    ->name('media:cleanup-orphans')
    ->everyFourHours()
    ->onOneServer();

// Social Auth: xóa token đã hết hạn > 30 ngày (giảm dữ liệu nhạy cảm lưu trữ)
Schedule::call(function () {
    SocialAccount::where('token_expires_at', '<', now()->subDays(30))->update([
        'access_token'  => null,
        'refresh_token' => null,
    ]);
})->weekly()->name('social-auth:cleanup-expired-tokens')->onOneServer();
