<?php

namespace App\View\Composers;

use App\Foundation\VerticalRegistry;
use App\Shared\Tenancy\TenantContext;
use Illuminate\View\View;

class SidebarComposer
{
    public function compose(View $view): void
    {
        $orgId = TenantContext::getOrganizationId();

        if (! $orgId) {
            $view->with('activeVerticals', collect());
            return;
        }

        $view->with('activeVerticals', VerticalRegistry::activeBlueprintVerticals($orgId)->values());
    }
}
