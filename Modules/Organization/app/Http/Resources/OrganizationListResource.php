<?php

namespace Modules\Organization\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrganizationListResource extends JsonResource
{
    private function _subscriptionStatus(): string
    {
        $sub = $this->planSubscriptions->first();
        if (!$sub) return 'none';
        if ($sub->onTrial()) return 'trial';
        if ($sub->active())  return 'active';
        if ($sub->canceled()) return 'canceled';
        return 'expired';
    }

    public function toArray(Request $request): array
    {
        // status is cast to OrganizationStatus enum via BaseOrganization::casts()
        $status = $this->status;

        return [
            'id'            => $this->id,
            'name'          => $this->name,
            'slug'          => $this->slug,
            'tax_code'      => $this->tax_code,
            'industry'      => $this->industry,
            'email'         => $this->email,
            'phone'         => $this->phone,
            'province_name' => $this->province?->name,
            'ward_name'     => $this->ward?->name,
            'members_count' => $this->members_count,

            'status_value' => $status->value,
            'status_label' => $status->label(),
            'status_badge' => $status->badgeClass(),

            'created_at' => $this->created_at?->format('d/m/Y'),

            'show_url'   => route('backend.organizations.show', $this->resource),
            'edit_url'   => route('backend.organizations.edit', $this->resource),
            'delete_url' => route('backend.organizations.destroy', $this->resource),

            // Suspended orgs can be deleted; active orgs require explicit deactivation first
            'can_delete' => $status->value !== 'active',

            // Subscription badge (loaded via eager load in ListOrganizationsHandler)
            'plan_name'           => $this->relationLoaded('planSubscriptions')
                ? ($this->planSubscriptions->first()?->plan?->name)
                : null,
            'subscription_status' => $this->relationLoaded('planSubscriptions')
                ? $this->_subscriptionStatus()
                : 'none',
        ];
    }
}
