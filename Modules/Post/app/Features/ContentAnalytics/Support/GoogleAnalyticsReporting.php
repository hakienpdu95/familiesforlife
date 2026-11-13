<?php

namespace Modules\Post\Features\ContentAnalytics\Support;

class GoogleAnalyticsReporting
{
    public static function configured(): bool
    {
        if (blank(config('analytics.property_id'))) {
            return false;
        }

        $credentials = config('analytics.service_account_credentials_json');

        return is_array($credentials) ? $credentials !== [] : (is_string($credentials) && is_file($credentials));
    }

    public static function consoleUrl(): string
    {
        $propertyId = preg_replace('/\D/', '', (string) config('analytics.property_id'));

        return $propertyId !== ''
            ? "https://analytics.google.com/analytics/web/#/p{$propertyId}/reports/intelligenthome"
            : 'https://analytics.google.com/analytics/web/';
    }
}
