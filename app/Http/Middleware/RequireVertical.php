<?php

namespace App\Http\Middleware;

use App\Foundation\VerticalRegistry;
use App\Shared\Tenancy\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireVertical
{
    public function handle(Request $request, Closure $next, ?string $code = null): Response
    {
        // Fall back to route parameter when no explicit code is passed (e.g. ->middleware('vertical'))
        $code ??= $request->route('vertical');

        if (! $code) {
            abort(400, 'Vertical code không được xác định.');
        }

        // super-admin (tài khoản quản trị hệ thống) xem/thao tác được vertical của MỌI tổ
        // chức — không giới hạn theo TenantContext hiện tại (thường mặc định là org "system").
        $vertical = $request->user()?->hasRole('super-admin')
            ? VerticalRegistry::resolveForSuperAdmin($code)
            : VerticalRegistry::resolveForOrganization(TenantContext::getOrganizationId(), $code);

        if (! $vertical || ! $vertical->isActive()) {
            // Phân biệt 404 (code không tồn tại ở thư viện) vs 403 (có nhưng chưa kích hoạt cho tổ chức này)
            if (! VerticalRegistry::exists($code)) {
                abort(404, "Vertical '{$code}' không tồn tại.");
            }
            abort(403, "Vertical '{$code}' chưa được kích hoạt cho tổ chức này.");
        }

        $request->attributes->set('_vertical', $vertical);

        // Exclude from parametersWithoutNulls() so the {vertical} string doesn't shift model-bound params in ControllerDispatcher.
        $request->route()->setParameter('vertical', null);

        return $next($request);
    }
}
