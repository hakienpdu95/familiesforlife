<?php
namespace Modules\Customer\Actions;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Customer\Data\Requests\UpdateCustomerData;
use Modules\Customer\Enums\CustomerType;
use Modules\Customer\Events\CustomerUpdated;
use Modules\Customer\Models\Customer;
use Modules\LeadSource\Models\LeadSource;

class UpdateCustomerAction
{
    use AsAction;

    /**
     * $canChangeOrg: true chỉ khi user thao tác không khoá org (super-admin) —
     * xem CustomerController::update(). User org-locked gửi organization_id lên
     * (nếu có, do bị can thiệp thủ công) vẫn bị bỏ qua ở đây, không tin client.
     */
    public function handle(Customer $customer, UpdateCustomerData $data, bool $canChangeOrg = false): Customer
    {
        $displayName = $this->resolveDisplayName($data);
        abort_if(! $displayName, 422, 'Vui lòng nhập họ tên hoặc tên doanh nghiệp.');

        $newOrgId = ($canChangeOrg && $data->organization_id !== null)
            ? $data->organization_id
            : $customer->organization_id;

        $assignedTo = $data->assigned_to;
        $sourceId   = $data->source_id;

        // Đổi org → assigned_to/source_id cũ (thuộc org cũ) có thể không còn hợp lệ ở org mới.
        if ($newOrgId !== $customer->organization_id) {
            if ($assignedTo && ! User::where('id', $assignedTo)->where('organization_id', $newOrgId)->exists()) {
                $assignedTo = null;
            }
            if ($sourceId) {
                $validSource = LeadSource::where('id', $sourceId)
                    ->where(fn ($q) => $q->where('organization_id', $newOrgId)->orWhere('is_global', true))
                    ->exists();
                if (! $validSource) $sourceId = null;
            }
        }

        $customer->update([
            'organization_id'      => $newOrgId,
            'customer_type'        => $data->customer_type,
            'display_name'         => $displayName,
            'primary_email'        => $data->primary_email,
            'primary_phone'        => $data->primary_phone,
            'lifecycle_stage'      => $data->lifecycle_stage ?? $customer->lifecycle_stage->value,
            'source_id'            => $sourceId,
            'assigned_to'          => $assignedTo,
            'first_name'           => $data->first_name,
            'last_name'            => $data->last_name,
            'gender'               => $data->gender,
            'date_of_birth'        => $data->date_of_birth,
            'company_name'         => $data->company_name,
            'tax_code'             => $data->tax_code,
            'industry'             => $data->industry,
            'company_size'         => $data->company_size,
            'representative_name'  => $data->representative_name,
            'representative_title' => $data->representative_title,
            'province_code'        => $data->province_code,
            'full_address'         => $data->full_address,
            'website'              => $data->website,
            'description'          => $data->description,
            'updated_by'           => Auth::id(),
        ]);

        SyncCustomerTagsAction::run($customer, $data->tag_ids ?? []);

        if ($data->meta !== null) {
            SyncCustomerMetaAction::run($customer, $data->meta);
        }

        event(new CustomerUpdated($customer));

        return $customer;
    }

    private function resolveDisplayName(UpdateCustomerData $data): ?string
    {
        if ($data->display_name) {
            return trim($data->display_name);
        }

        if ($data->customer_type === CustomerType::Business->value) {
            return $data->company_name ? trim($data->company_name) : null;
        }

        $name = trim(($data->first_name ?? '') . ' ' . ($data->last_name ?? ''));

        return $name !== '' ? $name : null;
    }
}
