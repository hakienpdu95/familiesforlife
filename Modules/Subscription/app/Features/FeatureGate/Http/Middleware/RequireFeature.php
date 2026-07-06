<?php

namespace Modules\Subscription\Features\FeatureGate\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Modules\Subscription\Features\FeatureGate\Support\SubscriptionContext;
use Symfony\Component\HttpFoundation\Response;

class RequireFeature
{
    public function handle(Request $request, Closure $next, string $featureSlug): Response
    {
        // system_admin và super-admin bypass tất cả feature gate
        $user = $request->user();
        if ($user?->hasAnyRole(['super-admin', 'system_admin'])) {
            return $next($request);
        }

        $ctx     = SubscriptionContext::get();
        $allowed = $ctx?->canUse($featureSlug) ?? false;

        if (!$allowed) {
            if ($request->expectsJson()) {
                return response()->json([
                    'error'       => 'feature_not_available',
                    'feature'     => $featureSlug,
                    'upgrade_url' => route('subscription.portal.plans'),
                ], 402);
            }

            return response()->view('subscription::partials.upgrade-wall', [
                'feature'    => $featureSlug,
                'plan'       => $ctx?->plan(),
                'upgradeUrl' => route('subscription.portal.plans'),
            ], 402);
        }

        return $next($request);
    }
}
