<?php

namespace Modules\Subscription\Features\FeatureGate\Support;

use App\Shared\Tenancy\Models\Organization;
use App\Shared\Tenancy\TenantContext;
use Laravelcm\Subscriptions\Models\Plan;
use Laravelcm\Subscriptions\Models\Subscription;
use Modules\Subscription\Models\OrganizationFeatureOverride;

final class SubscriptionContext
{
    /**
     * In-process store — lives for 1 request only.
     * Key: organization_id (int)
     */
    private static array $store = [];

    private array $featureMap  = [];
    private bool  $active      = false;
    private bool  $onTrial     = false;
    private bool  $gracePeriod = false;

    private function __construct(
        private readonly int           $orgId,
        private readonly ?Subscription $subscription,
    ) {}

    // ── Boot ──────────────────────────────────────────────────────────

    public static function boot(Organization $org): self
    {
        if (isset(static::$store[$org->id])) {
            return static::$store[$org->id];
        }

        $instance = new self($org->id, $org->planSubscription('main'));
        $instance->buildFeatureMap($org);

        static::$store[$org->id] = $instance;

        return $instance;
    }

    public static function get(): ?self
    {
        $orgId = TenantContext::getOrganizationId();
        return $orgId ? (static::$store[$orgId] ?? null) : null;
    }

    /**
     * Flush in-process entry for an org — call immediately after plan change
     * within the same request. Next request will reload from DB.
     */
    public static function flush(int $orgId): void
    {
        unset(static::$store[$orgId]);
    }

    public static function flushAll(): void
    {
        static::$store = [];
    }

    // ── Public API ────────────────────────────────────────────────────

    public function canUse(string $featureSlug): bool
    {
        if (!$this->active && !$this->gracePeriod) return false;

        // Grace period: block premium flags, allow basic modules
        if ($this->gracePeriod && str_starts_with($featureSlug, 'flag.')) {
            return false;
        }

        $value = $this->featureMap[$featureSlug] ?? null;
        if ($value === null) return false;

        return $value === '1' || $value === 'true';
    }

    /** 0 = unlimited */
    public function limitOf(string $limitSlug): int
    {
        return (int) ($this->featureMap[$limitSlug] ?? 0);
    }

    public function atLimit(string $limitSlug, int $currentCount): bool
    {
        $limit = $this->limitOf($limitSlug);
        return $limit > 0 && $currentCount >= $limit;
    }

    public function quotaRemaining(string $quotaSlug): int
    {
        if (!$this->subscription) return 0;
        return (int) $this->subscription->getFeatureRemainings($quotaSlug);
    }

    public function isActive(): bool      { return $this->active; }
    public function isOnTrial(): bool     { return $this->onTrial; }
    public function isGracePeriod(): bool { return $this->gracePeriod; }
    public function planSlug(): string    { return $this->subscription?->plan?->slug ?? 'none'; }
    public function plan(): ?Plan         { return $this->subscription?->plan; }

    // ── Internal ──────────────────────────────────────────────────────

    private function buildFeatureMap(Organization $org): void
    {
        $sub = $this->subscription;

        if (!$sub) {
            $this->active = false;
            return;
        }

        $sub->loadMissing('plan.features');

        $this->active      = $sub->active();
        $this->onTrial     = $sub->onTrial();
        $this->gracePeriod = !$sub->active()
            && $sub->plan?->hasGrace()
            && !$sub->ended();

        // 1. Plan features (base) — use null-safe: plan may be soft-deleted
        $map = [];
        foreach ($sub->plan?->features ?? [] as $feature) {
            $map[$feature->slug] = $feature->value;
        }

        // 2. Active overrides WIN over plan features
        OrganizationFeatureOverride::where('organization_id', $org->id)
            ->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()))
            ->each(function ($override) use (&$map): void {
                $map[$override->feature_slug] = $override->value;
            });

        $this->featureMap = $map;
    }
}
