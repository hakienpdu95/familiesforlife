<?php

namespace Modules\Deployment\Actions;

use App\Foundation\VerticalDefinition;
use App\Shared\Tenancy\TenantContext;
use DomainException;
use Illuminate\Support\Str;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Deployment\Data\CreateVerticalProjectData;
use Modules\Employee\Models\Employee;
use Modules\Project\Models\Project;

class CreateVerticalProjectAction
{
    use AsAction;

    public function handle(CreateVerticalProjectData $data, VerticalDefinition $vertical): Project
    {
        $user = auth()->user();

        // projects.owner_id là FK tới employees.id (KHÔNG phải users.id) — người tạo
        // project phải có bản ghi Employee tương ứng trong tổ chức hiện tại.
        $employee = Employee::withoutTenant()
            ->where('organization_id', TenantContext::getOrganizationId())
            ->where('user_id', $user->id)
            ->first();

        if (! $employee) {
            throw new DomainException(
                "Người dùng #{$user->id} chưa có hồ sơ Employee trong tổ chức hiện tại — không thể làm chủ dự án (owner)."
            );
        }

        return Project::create([
            'uuid'            => (string) Str::uuid(),
            'organization_id' => TenantContext::getOrganizationId(),
            'owner_id'        => $employee->id,
            'code'            => strtoupper(trim($data->code)),
            'name'            => $data->name,
            'description'     => $data->description,
            'category'        => $vertical->code(),
            'vertical_code'   => $vertical->code(),
            'status'          => $data->status->value,
            'priority'        => 'medium',
            'start_date'      => $data->start_date,
            'end_date'        => $data->end_date,
            'created_by'      => $user->id,
            'updated_by'      => $user->id,
        ]);
    }
}
