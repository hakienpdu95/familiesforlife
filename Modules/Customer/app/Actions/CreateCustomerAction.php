<?php
namespace Modules\Customer\Actions;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Customer\Data\Requests\StoreCustomerData;
use Modules\Customer\Enums\CustomerLifecycleStage;
use Modules\Customer\Enums\CustomerType;
use Modules\Customer\Events\CustomerCreated;
use Modules\Customer\Models\Customer;

class CreateCustomerAction
{
    use AsAction;

    public function handle(StoreCustomerData $data, int $orgId): Customer
    {
        $displayName = $this->resolveDisplayName($data);
        abort_if(! $displayName, 422, 'Vui lòng nhập họ tên hoặc tên doanh nghiệp.');

        $hash = $this->dedupHash($data->primary_email, $data->primary_phone);

        if ($hash) {
            $existing = Customer::where('organization_id', $orgId)
                ->where('dedup_hash', $hash)->first();
            if ($existing) return $existing;
        }

        $customer = DB::transaction(function () use ($data, $orgId, $hash, $displayName): Customer {
            $customer = Customer::create([
                'organization_id'      => $orgId,
                'customer_type'        => $data->customer_type,
                'display_name'         => $displayName,
                'primary_email'        => $data->primary_email,
                'primary_phone'        => $data->primary_phone,
                'lifecycle_stage'      => $data->lifecycle_stage ?? CustomerLifecycleStage::Active->value,
                'source_id'            => $data->source_id,
                'assigned_to'          => $data->assigned_to,
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
                'dedup_hash'           => $hash,
                'created_by'           => Auth::id(),
            ]);

            if (! empty($data->tag_ids)) {
                SyncCustomerTagsAction::run($customer, $data->tag_ids);
            }

            if (! empty($data->meta)) {
                SyncCustomerMetaAction::run($customer, $data->meta);
            }

            return $customer;
        });

        event(new CustomerCreated($customer));

        return $customer;
    }

    private function dedupHash(?string $email, ?string $phone): ?string
    {
        $key = strtolower(trim($email ?? ''))
            ?: preg_replace('/\D/', '', $phone ?? '');

        return $key ? md5($key) : null;
    }

    private function resolveDisplayName(StoreCustomerData $data): ?string
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
