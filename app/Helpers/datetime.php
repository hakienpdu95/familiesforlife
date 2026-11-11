<?php

use App\Models\SystemSetting;
use Illuminate\Support\Carbon;

if (! function_exists('system_setting')) {
    function system_setting(): SystemSetting
    {
        return SystemSetting::current();
    }
}

if (! function_exists('app_locale')) {
    function app_locale(): string
    {
        return app()->getLocale();
    }
}

if (! function_exists('format_date')) {
    function format_date(DateTimeInterface|string|null $value): ?string
    {
        if (blank($value)) {
            return null;
        }

        $setting = SystemSetting::current();

        return Carbon::parse($value)->setTimezone($setting->timezone)->format($setting->date_format);
    }
}

if (! function_exists('format_datetime')) {
    function format_datetime(DateTimeInterface|string|null $value, bool $withSeconds = false): ?string
    {
        if (blank($value)) {
            return null;
        }

        $setting = SystemSetting::current();
        $time = $withSeconds ? str_replace('i', 'i:s', $setting->time_format) : $setting->time_format;

        return Carbon::parse($value)->setTimezone($setting->timezone)->format($setting->date_format.' '.$time);
    }
}
