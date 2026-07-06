<?php

namespace Modules\Lead\Actions;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Lead\Data\LeadActivityData;
use Modules\Lead\Data\Requests\StoreLeadData;
use Modules\Lead\Enums\LeadActivityType;
use Modules\Lead\Enums\LeadStatus;
use Modules\Lead\Events\LeadCreated;
use Modules\Lead\Models\Lead;
use Modules\Lead\Models\LeadContact;
use Modules\LeadPipelineStage\Models\LeadPipelineStage;
use Modules\Lead\Models\LeadStageHistory;

class CreateLeadAction
{
    use AsAction;

    public function handle(StoreLeadData $data, int $orgId): Lead
    {
        // 0. Idempotency — Workflow retry sẽ không tạo lead trùng
        if ($data->idempotent_key) {
            $existing = Lead::withoutGlobalScopes()
                ->where('idempotent_key', $data->idempotent_key)
                ->first();
            if ($existing) {
                return $existing;
            }
        }

        $lead = DB::transaction(function () use ($data, $orgId) {

            // 1. Resolve / create contact (dedup per org + hash)
            $contact = $this->resolveContact($data, $orgId);

            // 2. Load stage for snapshot label
            $stage = LeadPipelineStage::findOrFail($data->stage_id);

            // 3. Create lead
            $lead = Lead::create([
                'organization_id'     => $data->organization_id,
                'contact_id'          => $contact->id,
                'contact_name'        => $contact->full_name,
                'contact_phone'       => $contact->phone,
                'contact_company'     => $contact->company,
                'stage_id'            => $data->stage_id,
                'stage_changed_at'    => now(),
                'source_id'           => $data->source_id,
                'source_detail'       => $data->source_detail,
                'assigned_to'         => $data->assigned_to,
                'assigned_at'         => $data->assigned_to ? now() : null,
                'expected_value'      => $data->expected_value,
                'currency'            => $data->currency,
                'expected_close_date' => $data->expected_close_date,
                'title'               => $data->title,
                'description'         => $data->description,
                'survey_response_id'  => $data->survey_response_id,
                'survey_band_code'    => $data->survey_band_code,
                'survey_score'        => $data->survey_score,
                'idempotent_key'      => $data->idempotent_key,
                'status'              => LeadStatus::Active,
                'last_activity_at'    => now(),
                'created_by'          => Auth::id(),
            ]);

            // 4. Counter cache on contact
            $contact->increment('lead_count');

            // 5. Record initial stage history
            LeadStageHistory::create([
                'lead_id'         => $lead->id,
                'organization_id' => $lead->organization_id,
                'stage_from_id'   => null,
                'stage_to_id'     => $lead->stage_id,
                'stage_to_label'  => $stage->label,
                'changed_by'      => Auth::id(),
                'changed_by_name' => Auth::user()?->name,
                'changed_at'      => now(),
                'created_at'      => now(),
            ]);

            // 6. Log system activity inline (avoids double-count inside transaction)
            LogLeadActivityAction::run(new LeadActivityData(
                leadId:      $lead->id,
                orgId:       $lead->organization_id,
                type:        LeadActivityType::System->value,
                title:       'Tạo lead mới',
                description: null,
                completedAt: now()->toDateTimeString(),
                actorId:     Auth::id(),
                actorName:   Auth::user()?->name,
            ));

            return $lead;
        });

        // 7. Fire domain event after transaction commits
        event(new LeadCreated($lead));

        // 8. Sync tags (outside transaction — safe to retry)
        if (!empty($data->tag_ids)) {
            SyncLeadTagsAction::run($lead, $data->tag_ids);
        }

        // 9. Async scoring after transaction commit
        ScoreLeadAction::dispatchForLead($lead->id);

        return $lead;
    }

    /**
     * Find or create a contact by dedup hash within the org.
     * Merges non-null fields into existing contact if found.
     */
    private function resolveContact(StoreLeadData $data, int $orgId): LeadContact
    {
        $hash = $data->contactDedupHash();

        if ($hash) {
            $existing = LeadContact::where('organization_id', $orgId)
                ->where('dedup_hash', $hash)
                ->first();

            if ($existing) {
                $this->mergeContactFields($existing, $data);
                return $existing;
            }
        }

        return LeadContact::create([
            'organization_id' => $orgId,
            'full_name'       => $data->contact_name,
            'email'           => $data->contact_email,
            'phone'           => $data->contact_phone,
            'phone_alt'       => $data->contact_phone_alt,
            'company'         => $data->contact_company,
            'job_title'       => $data->contact_job_title,
            'website'         => $data->contact_website,
            'address'         => $data->contact_address,
            'province_code'   => $data->province_code,
            'province_name'   => $data->province_name,
            'ward_code'       => $data->ward_code,
            'ward_name'       => $data->ward_name,
            'dedup_hash'      => $hash,
            'created_by'      => Auth::id(),
        ]);
    }

    /** Update only non-null, non-empty fields — never overwrite with null. */
    private function mergeContactFields(LeadContact $contact, StoreLeadData $data): void
    {
        $updates = array_filter([
            'company'       => $data->contact_company,
            'job_title'     => $data->contact_job_title,
            'website'       => $data->contact_website,
            'phone_alt'     => $data->contact_phone_alt,
            'address'       => $data->contact_address,
            'province_code' => $data->province_code,
            'province_name' => $data->province_name,
            'ward_code'     => $data->ward_code,
            'ward_name'     => $data->ward_name,
        ], fn ($v) => $v !== null && $v !== '');

        if ($updates) {
            $contact->update($updates);
        }
    }
}
