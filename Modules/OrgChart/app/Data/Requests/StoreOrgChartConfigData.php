<?php

namespace Modules\OrgChart\Data\Requests;

use Modules\OrgChart\Enums\OrgChartGroupBy;
use Modules\OrgChart\Enums\OrgChartViewType;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Data;

class StoreOrgChartConfigData extends Data
{
    public function __construct(
        #[Nullable]
        public readonly ?int $organization_id,

        #[Required, StringType, Max(255)]
        public readonly string $name,

        public readonly OrgChartViewType $view_type,

        public readonly OrgChartGroupBy $group_by,

        #[Nullable]
        public readonly ?int $scope_branch_id,

        public readonly bool $show_avatar        = true,
        public readonly bool $show_job_title      = true,
        public readonly bool $show_employee_code  = false,
        public readonly bool $show_department     = true,
        public readonly bool $show_branch         = false,

        #[Min(1), Max(10)]
        public readonly int $max_depth            = 5,

        public readonly bool $expand_by_default   = false,
        public readonly bool $is_default          = false,
    ) {}

    public static function rules(): array
    {
        return [
            'organization_id' => ['nullable', 'integer', 'exists:organizations,id'],
            'scope_branch_id' => ['nullable', 'integer', 'exists:branches,id'],
            'max_depth'       => ['required', 'integer', 'min:1', 'max:10'],
        ];
    }
}
