<?php

namespace Modules\OrgChart\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\OrgChart\Enums\OrgChartGroupBy;
use Modules\OrgChart\Enums\OrgChartViewType;

class OrgChartConfigListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $viewType = $this->view_type instanceof OrgChartViewType
            ? $this->view_type
            : OrgChartViewType::tryFrom($this->view_type);

        $groupBy = $this->group_by instanceof OrgChartGroupBy
            ? $this->group_by
            : OrgChartGroupBy::tryFrom($this->group_by);

        return [
            'id'           => $this->id,
            'name'         => $this->name,
            'view_type'    => $viewType?->value ?? $this->view_type,
            'view_type_label' => $viewType?->label() ?? $this->view_type,
            'group_by'     => $groupBy?->value ?? $this->group_by,
            'group_by_label' => $groupBy?->label() ?? $this->group_by,
            'scope_branch' => $this->scopeBranch ? $this->scopeBranch->name . ' (' . $this->scopeBranch->code . ')' : null,
            'max_depth'    => $this->max_depth,
            'is_default'   => (bool) $this->is_default,
            'created_at'   => $this->created_at?->format('d/m/Y'),
            'show_url'     => route('backend.org-charts.show', $this->resource),
            'edit_url'     => route('backend.org-charts.edit', $this->resource),
            'delete_url'   => route('backend.org-charts.destroy', $this->resource),
        ];
    }
}
