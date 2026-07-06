<?php

use Modules\Subscription\Features\FeatureGate\Support\SubscriptionContext;

if (!function_exists('org_can')) {
    function org_can(string $featureSlug): bool
    {
        return SubscriptionContext::get()?->canUse($featureSlug) ?? false;
    }
}

if (!function_exists('org_limit')) {
    function org_limit(string $limitSlug): int
    {
        return SubscriptionContext::get()?->limitOf($limitSlug) ?? 0;
    }
}

if (!function_exists('org_at_limit')) {
    function org_at_limit(string $limitSlug, int $current): bool
    {
        return SubscriptionContext::get()?->atLimit($limitSlug, $current) ?? false;
    }
}

if (!function_exists('org_quota')) {
    function org_quota(string $quotaSlug): int
    {
        return SubscriptionContext::get()?->quotaRemaining($quotaSlug) ?? 0;
    }
}
