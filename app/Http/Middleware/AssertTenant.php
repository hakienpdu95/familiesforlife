<?php

namespace App\Http\Middleware;

use App\Shared\Tenancy\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AssertTenant
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! TenantContext::isSet()) {
            abort(403, 'Tenant context chưa được thiết lập.');
        }

        return $next($request);
    }
}
