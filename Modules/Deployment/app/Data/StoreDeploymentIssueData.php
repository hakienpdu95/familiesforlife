<?php

namespace Modules\Deployment\Data;

use Illuminate\Validation\Rule;
use Modules\Deployment\Enums\IssueSeverity;
use Modules\Deployment\Models\DeploymentTarget;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Support\Validation\ValidationContext;

class StoreDeploymentIssueData extends Data
{
    public function __construct(
        public readonly int           $deployment_target_id,
        public readonly int           $project_id,
        public readonly string        $title,
        public readonly ?string       $description,
        public readonly IssueSeverity $severity,
        public readonly ?string       $issue_type,
        public readonly ?string       $severity_detail,
        public readonly ?int          $owner_id,
    ) {}

    public static function rules(ValidationContext $context): array
    {
        // owner_id phải thuộc đúng tổ chức đang triển khai của target được chọn — tra theo
        // deployment_target_id thực sự submit (không có route model binding ở bước tạo mới).
        $targetId = $context->payload['deployment_target_id'] ?? null;
        $orgId    = $targetId
            ? DeploymentTarget::withoutTenant()->find($targetId)?->target_organization_id
            : null;

        return [
            'deployment_target_id' => ['required', 'integer', 'exists:deployment_targets,id'],
            'project_id'           => ['required', 'integer', 'exists:projects,id'],
            'title'                => ['required', 'string', 'max:255'],
            'description'          => ['nullable', 'string', 'max:5000'],
            'severity'             => ['required', Rule::enum(IssueSeverity::class)],
            // Mã tự do, tra theo danh mục issue_type do từng tổ chức tự định nghĩa
            // (VerticalConfigItem config_group=issue_type) — không ràng buộc enum cố định.
            'issue_type'           => ['nullable', 'string', 'max:50'],
            'severity_detail'      => ['nullable', 'string', 'max:2000'],
            'owner_id'             => [
                'nullable', 'integer',
                $orgId
                    ? Rule::exists('users', 'id')->where('organization_id', $orgId)
                    : 'exists:users,id',
            ],
        ];
    }

    public static function messages(): array
    {
        return [
            'title.required'                => 'Vui lòng nhập tiêu đề issue.',
            'deployment_target_id.required' => 'Vui lòng chọn đối tượng triển khai.',
            'severity.required'             => 'Vui lòng chọn mức độ nghiêm trọng.',
        ];
    }
}
