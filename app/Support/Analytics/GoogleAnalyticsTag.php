<?php

namespace App\Support\Analytics;

use App\Models\SystemSetting;
use App\Models\User;

class GoogleAnalyticsTag
{
    public static function enabledForEnvironment(): bool
    {
        $flag = config('services.google_analytics.tracking_enabled');

        return $flag === null ? app()->isProduction() : filter_var($flag, FILTER_VALIDATE_BOOL);
    }

    public static function measurementIdFor(?User $user): ?string
    {
        if (! static::enabledForEnvironment() || $user?->isPlatform()) {
            return null;
        }

        return SystemSetting::gaMeasurementId();
    }
}
