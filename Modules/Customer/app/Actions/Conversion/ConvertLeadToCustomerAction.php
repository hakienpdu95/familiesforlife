<?php
namespace Modules\Customer\Actions\Conversion;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Customer\Actions\SyncCustomerTagsAction;
use Modules\Customer\Enums\CustomerLifecycleStage;
use Modules\Customer\Enums\CustomerType;
use Modules\Customer\Events\CustomerConverted;
use Modules\Customer\Models\Customer;
use Modules\Lead\Models\Lead;

class ConvertLeadToCustomerAction
{
    use AsAction;

    public function handle(Lead $lead): Customer
    {
        $customer = DB::transaction(function () use ($lead): Customer {

            // 1. Dedup check — prefer email, fall back to phone
            $email    = $lead->contact?->email  ?? null;
            $phone    = $lead->contact?->phone  ?? $lead->contact_phone ?? null;
            $rawKey   = $email ?? $phone;
            $dedupHash = $rawKey ? md5(mb_strtolower(trim($rawKey))) : null;

            // 2. Find existing customer or create
            $customer = null;
            if ($dedupHash) {
                $customer = Customer::withoutGlobalScopes()
                    ->where('organization_id', $lead->organization_id)
                    ->where('dedup_hash', $dedupHash)
                    ->first();
            }

            if ($customer) {
                // Upgrade to Active if currently Prospect
                if ($customer->lifecycle_stage === CustomerLifecycleStage::Prospect) {
                    $customer->update(['lifecycle_stage' => CustomerLifecycleStage::Active]);
                }
            } else {
                // Build display_name from lead data
                $contact     = $lead->contact;
                $displayName = $contact?->full_name ?? $lead->contact_name
                    ?? $lead->contact_company
                    ?? 'Không rõ';

                $isCompany = !empty($lead->contact_company) && empty($lead->contact_name);

                $customer = Customer::create([
                    'organization_id'       => $lead->organization_id,
                    'customer_type'         => $isCompany ? CustomerType::Business->value : CustomerType::Individual->value,
                    'display_name'          => $displayName,
                    'primary_email'         => $contact?->email  ?? null,
                    'secondary_email'       => null,
                    'primary_phone'         => $contact?->phone  ?? $lead->contact_phone ?? null,
                    'secondary_phone'       => $contact?->phone_alt ?? null,
                    'website'               => $contact?->website ?? null,
                    'province_code'         => $contact?->province_code ?? null,
                    'province_name'         => $contact?->province_name ?? null,
                    'ward_code'             => $contact?->ward_code ?? null,
                    'ward_name'             => $contact?->ward_name ?? null,
                    'address_line'          => $contact?->address ?? null,
                    'first_name'            => $contact ? $this->parseFirstName($contact->full_name) : null,
                    'last_name'             => $contact ? $this->parseLastName($contact->full_name) : $lead->contact_name,
                    'company_name'          => $isCompany ? ($lead->contact_company ?? $displayName) : $lead->contact_company,
                    'lifecycle_stage'       => CustomerLifecycleStage::Active->value,
                    'source_id'             => $lead->source_id,
                    'assigned_to'           => $lead->assigned_to,
                    'dedup_hash'            => $dedupHash,
                    'converted_from_lead_id' => $lead->id,
                    'created_by'            => Auth::id(),
                ]);

                // Copy lead tags if any
                if ($lead->tags->isNotEmpty()) {
                    SyncCustomerTagsAction::run($customer, []);
                }
            }

            // 3. Link lead → customer
            $lead->update(['customer_id' => $customer->id]);

            return $customer;
        });

        event(new CustomerConverted($customer, $lead));

        return $customer;
    }

    private function parseFirstName(?string $fullName): ?string
    {
        if (!$fullName) return null;
        $parts = explode(' ', trim($fullName));
        return count($parts) > 1 ? $parts[0] : null;
    }

    private function parseLastName(?string $fullName): ?string
    {
        if (!$fullName) return null;
        $parts = explode(' ', trim($fullName));
        return count($parts) > 1 ? implode(' ', array_slice($parts, 1)) : $fullName;
    }
}
