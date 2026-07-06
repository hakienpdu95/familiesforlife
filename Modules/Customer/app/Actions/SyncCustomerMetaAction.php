<?php
namespace Modules\Customer\Actions;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Customer\Enums\MetaValueType;
use Modules\Customer\Models\Customer;
use Modules\Customer\Models\CustomerFieldDefinition;
use Modules\Customer\Models\CustomerMeta;

class SyncCustomerMetaAction
{
    use AsAction;

    public function handle(Customer $customer, array $values): void
    {
        foreach ($values as $definitionId => $rawValue) {
            $def = CustomerFieldDefinition::find((int) $definitionId);
            if (! $def || ! $def->is_active) continue;

            if ($rawValue === null || $rawValue === '') {
                CustomerMeta::where('customer_id', $customer->id)
                    ->where('definition_id', $def->id)
                    ->delete();
                continue;
            }

            $payload = match ($def->value_type) {
                MetaValueType::Integer => ['val_integer' => (int) $rawValue],
                MetaValueType::Decimal => ['val_decimal' => (float) $rawValue],
                MetaValueType::Boolean => ['val_boolean' => (bool) $rawValue],
                MetaValueType::Date    => ['val_date'    => $rawValue],
                default                => ['val_string'  => (string) $rawValue],
            };

            CustomerMeta::updateOrCreate(
                ['customer_id' => $customer->id, 'definition_id' => $def->id],
                $payload
            );
        }
    }
}
