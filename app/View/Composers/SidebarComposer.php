<?php

namespace App\View\Composers;

use App\Foundation\Vertical\DatabaseVertical;
use App\Foundation\Vertical\VerticalTemplate;
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

        // ->toBase(): Eloquent\Collection::map() chỉ downgrade về Support\Collection khi
        // KHÔNG rỗng và có phần tử không phải Model — với query rỗng (rất phổ biến, đa số
        // tổ chức chưa có vertical_templates) nó vẫn giữ nguyên kiểu Eloquent, khiến merge()
        // phía dưới cố gọi getKey() trên DatabaseVertical/BlueprintToVerticalDefinitionAdapter
        // (không phải Model) và crash.
        $templateVerticals = VerticalTemplate::where('organization_id', $orgId)
            ->where('status', 'active')
            ->where('is_active', true)
            ->get()
            ->map(fn (VerticalTemplate $template) => new DatabaseVertical($template))
            ->toBase();

        // Vertical được deploy từ Business Blueprint mới (song song vertical_templates).
        $blueprintVerticals = VerticalRegistry::activeBlueprintVerticals($orgId);

        $view->with('activeVerticals', $templateVerticals->merge($blueprintVerticals)->values());
    }
}
