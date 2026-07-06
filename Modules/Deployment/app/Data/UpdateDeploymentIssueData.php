<?php

namespace Modules\Deployment\Data;

use Illuminate\Validation\Rule;
use Modules\Deployment\Enums\IssueSeverity;
use Modules\Deployment\Enums\IssueStatus;
use Spatie\LaravelData\Data;

class UpdateDeploymentIssueData extends Data
{
    public function __construct(
        public readonly string        $title,
        public readonly ?string       $description,
        public readonly IssueSeverity $severity,
        public readonly ?string       $issue_type,
        public readonly ?string       $severity_detail,
        public readonly IssueStatus   $status,
        public readonly ?int          $owner_id,
    ) {}

    public static function rules(): array
    {
        return [
            'title'           => ['required', 'string', 'max:255'],
            'description'     => ['nullable', 'string', 'max:5000'],
            'severity'        => ['required', Rule::enum(IssueSeverity::class)],
            'issue_type'      => ['nullable', 'string', 'max:50'],
            'severity_detail' => ['nullable', 'string', 'max:2000'],
            'status'          => ['required', Rule::enum(IssueStatus::class)],
            'owner_id'        => ['nullable', 'integer', 'exists:users,id'],
        ];
    }
}
