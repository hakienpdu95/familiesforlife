<?php

namespace App\Http\Middleware;

use App\Shared\Tenancy\Models\Organization;
use App\Shared\Tenancy\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

/**
 * Resolves the current tenant (Organization) from the request and populates TenantContext.
 *
 * Resolution order:
 *   1. Subdomain:          acme.minhan.app → slug = 'acme'
 *   2. Request header:     X-Organization-ID: 42
 *   3. Auth user's org:    auth()->user()->organization_id
 *   4. Session:            organization_id stored from previous request
 *
 * Non-blocking: if no organization is resolved, the request continues as a guest/public.
 * Routes that require a tenant must use the 'tenant' middleware alias to assert context.
 */
class IdentifyOrganization
{
    public function handle(Request $request, Closure $next): Response
    {
        $organization = $this->resolveFromSubdomain($request)
            ?? $this->resolveFromHeader($request)
            ?? $this->resolveFromAuthUser($request)
            ?? $this->resolveFromSession($request)
            ?? $this->resolveDefaultForSuperAdmin($request);

        if ($organization?->isActive()) {
            TenantContext::set($organization);
            $request->session()->put('organization_id', $organization->id);
        }

        return $next($request);
    }

    private function resolveFromSubdomain(Request $request): ?Organization
    {
        $host = $request->getHost();
        $appDomain = config('app.domain', parse_url(config('app.url'), PHP_URL_HOST));

        if (!$appDomain || !str_ends_with($host, '.' . $appDomain)) {
            return null;
        }

        $slug = str_replace('.' . $appDomain, '', $host);

        if (empty($slug) || $slug === $appDomain) {
            return null;
        }

        return $this->findBySlug($slug);
    }

    private function resolveFromHeader(Request $request): ?Organization
    {
        $orgId = $request->header('X-Organization-ID');

        if (!$orgId || !is_numeric($orgId)) {
            return null;
        }

        $org = $this->findById((int) $orgId);

        if (!$org) {
            return null;
        }

        // Phải xác minh user là thành viên của org được yêu cầu.
        // Super-admin (organization_id = null) được phép bypass.
        $user = $request->user();
        if ($user && $user->organization_id === null && $user->hasRole('super-admin')) {
            return $org;
        }

        if ($user && \Modules\Organization\Models\OrganizationMember::where('organization_id', $org->id)
                ->where('user_id', $user->id)
                ->exists()) {
            return $org;
        }

        return null;
    }

    private function resolveFromAuthUser(Request $request): ?Organization
    {
        $user = $request->user();

        if (!$user || !$user->organization_id) {
            return null;
        }

        return $this->findById($user->organization_id);
    }

    private function resolveFromSession(Request $request): ?Organization
    {
        if (!$request->hasSession()) {
            return null;
        }

        $orgId = $request->session()->get('organization_id');

        if (!$orgId) {
            return null;
        }

        return $this->findById((int) $orgId);
    }

    /**
     * Super-admin không thuộc org nào → dùng org hệ thống (is_system=true) làm tenant context.
     * Fallback về org active đầu tiên nếu chưa seed SystemOrganizationSeeder.
     * Session sẽ ghi nhớ cho các request tiếp theo.
     * Có thể đổi sang org cụ thể bằng header X-Organization-ID.
     */
    private function resolveDefaultForSuperAdmin(Request $request): ?Organization
    {
        $user = $request->user();

        if (!$user || $user->organization_id !== null || !$user->hasRole('super-admin')) {
            return null;
        }

        return Organization::active()->system()->first()
            ?? Organization::active()->orderBy('id')->first();
    }

    private function findBySlug(string $slug): ?Organization
    {
        // Cache ID only (not the model) để tránh __PHP_Incomplete_Class khi deserialize
        $id = Cache::remember(
            "org.slug.{$slug}",
            now()->addMinutes(5),
            fn () => Organization::active()->bySlug($slug)->value('id')
        );

        return $id ? Organization::find($id) : null;
    }

    private function findById(int $id): ?Organization
    {
        // Cache chỉ ID đã xác nhận tồn tại, KHÔNG cache full model.
        // Luôn load fresh model để isActive() phản ánh đúng trạng thái hiện tại.
        // TTL ngắn (2 phút) để giảm DB hits mà vẫn phản hồi nhanh khi org bị suspend.
        $exists = Cache::remember(
            "org.exists.{$id}",
            now()->addMinutes(2),
            fn () => Organization::where('id', $id)->exists()
        );

        return $exists ? Organization::find($id) : null;
    }
}
