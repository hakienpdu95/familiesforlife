<?php

namespace Modules\Leave\Data\Requests;

use Illuminate\Validation\Rule;
use Modules\Leave\Enums\LeaveType;
use Spatie\LaravelData\Data;

class StoreLeaveRequestData extends Data
{
    public function __construct(
        public readonly int       $organization_id,
        public readonly int       $employee_id,
        public readonly LeaveType $leave_type,
        public readonly string    $date_from,
        public readonly string    $date_to,
        public readonly ?string   $reason,
        public readonly ?string   $attachment_url,
    ) {}

    public static function rules(): array
    {
        $orgId = (int) request('organization_id');

        return [
            'organization_id' => ['required', 'integer', 'exists:organizations,id'],
            'employee_id'    => ['required', 'integer', Rule::exists('employees', 'id')->where('organization_id', $orgId)],
            'leave_type'     => ['required', 'string', Rule::enum(LeaveType::class)],
            'date_from'      => ['required', 'date'],
            'date_to'        => ['required', 'date', 'after_or_equal:date_from'],
            'reason'         => ['nullable', 'string', 'max:1000'],
            'attachment_url' => ['nullable', 'string', 'max:500'],
        ];
    }

    public static function messages(): array
    {
        return [
            'organization_id.required' => 'Vui lòng chọn tổ chức.',
            'organization_id.exists'   => 'Tổ chức không hợp lệ.',
            'employee_id.required'     => 'Vui lòng chọn nhân viên.',
            'employee_id.exists'       => 'Nhân viên không thuộc tổ chức đã chọn.',
            'leave_type.required'      => 'Vui lòng chọn loại nghỉ.',
            'date_from.required'       => 'Vui lòng chọn ngày bắt đầu.',
            'date_to.required'         => 'Vui lòng chọn ngày kết thúc.',
            'date_to.after_or_equal'   => 'Ngày kết thúc phải sau hoặc bằng ngày bắt đầu.',
        ];
    }
}
