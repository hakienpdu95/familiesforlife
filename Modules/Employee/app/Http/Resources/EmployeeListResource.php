<?php

namespace Modules\Employee\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EmployeeListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $status = $this->status;
        $type   = $this->employment_type;

        return [
            'id'            => $this->id,
            'uuid'          => $this->uuid,
            'employee_code' => $this->employee_code,
            'full_name'     => $this->full_name,
            'email'         => $this->email,
            'phone'         => $this->phone,
            'avatar_url'    => $this->getMediaUrl('avatar', 'thumb') ?: $this->avatar_url,

            'status'       => $status->value,
            'status_label' => $status->label(),
            'status_badge' => $status->badgeClass(),

            'employment_type'       => $type->value,
            'employment_type_label' => $type->label(),
            'employment_type_badge' => $type->badgeClass(),

            'branch_name'   => $this->branch?->name,
            'branch_code'   => $this->branch?->code,
            'dept_name'     => $this->department?->name,
            'dept_code'     => $this->department?->code,
            'job_title'     => $this->jobTitle?->name,
            'job_level'     => $this->jobTitle?->level,
            'manager_name'  => $this->manager?->full_name,

            // Snapshot fields (fast display without extra joins)
            'snap_branch_name' => $this->snap_branch_name,
            'snap_dept_name'   => $this->snap_dept_name,
            'snap_job_title'   => $this->snap_job_title,
            'snap_job_level'   => $this->snap_job_level,

            'hired_at'   => $this->hired_at?->format('d/m/Y'),
            'created_at' => $this->created_at?->format('d/m/Y'),

            'show_url'   => route('backend.employees.show', $this->resource),
            'edit_url'   => route('backend.employees.edit', $this->resource),
            'delete_url' => route('backend.employees.destroy', $this->resource),
        ];
    }
}
