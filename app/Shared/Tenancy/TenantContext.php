<?php

namespace App\Shared\Tenancy;

use App\Foundation\Exceptions\TenantNotSetException;
use App\Shared\Tenancy\Models\Organization;

/**
 * Static tenant context — holds the resolved Organization for the current request lifecycle.
 *
 * Set by IdentifyOrganization middleware. All tenant-scoped queries and actions read from here.
 */
final class TenantContext
{
    private static ?Organization $organization = null;

    public static function set(Organization $organization): void
    {
        static::$organization = $organization;
    }

    public static function get(): ?Organization
    {
        return static::$organization;
    }

    /** Resolves the current organization or throws if not set. */
    public static function resolve(): Organization
    {
        if (static::$organization === null) {
            throw new TenantNotSetException();
        }

        return static::$organization;
    }

    public static function getOrganizationId(): ?int
    {
        return static::$organization?->id;
    }

    public static function isSet(): bool
    {
        return static::$organization !== null;
    }

    /** Clear context — used in tests and at end of tenant-scoped jobs. */
    public static function flush(): void
    {
        static::$organization = null;
    }

    /**
     * Execute a callback within a specific organization's context, then restore the previous state.
     * Useful in queue jobs and scheduled tasks that switch between tenants.
     */
    public static function runForOrganization(Organization $organization, callable $callback): mixed
    {
        $previous = static::$organization;

        try {
            static::set($organization);
            return $callback();
        } finally {
            static::$organization = $previous;
        }
    }
}
