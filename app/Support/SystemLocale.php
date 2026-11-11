<?php

namespace App\Support;

use App\Models\SystemSetting;
use Illuminate\Support\Facades\App;

class SystemLocale
{
    public static function apply(): void
    {
        $locale = SystemSetting::activeLocale();

        if (App::getLocale() !== $locale) {
            App::setLocale($locale);
        }
    }
}
