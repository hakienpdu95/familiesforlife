<?php

namespace Modules\LeadPipelineStage\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\LeadPipelineStage\Data\Requests\CreateStageData;
use Modules\LeadPipelineStage\Events\StageCreated;
use Modules\LeadPipelineStage\Models\LeadPipelineStage;

class CreateStageAction
{
    use AsAction;

    public function handle(CreateStageData $data): LeadPipelineStage
    {
        // Enforce: org cannot duplicate a code that exists globally or within org
        $this->assertCodeUnique($data->code, $data->organization_id);

        $stage = DB::transaction(fn () => LeadPipelineStage::create([
            'organization_id' => $data->organization_id,
            'is_global'       => false,
            'code'            => $data->code,
            'label'           => $data->label,
            'color'           => $data->color,
            'sort_order'      => $data->sort_order,
            'probability'     => $data->probability,
            'is_won'          => $data->is_won,
            'is_lost'         => $data->is_lost,
            'is_active'       => true,
        ]));

        event(new StageCreated($stage));

        return $stage;
    }

    private function assertCodeUnique(string $code, int $orgId): void
    {
        $exists = LeadPipelineStage::query()
            ->where('code', $code)
            ->where(function ($q) use ($orgId) {
                $q->where('organization_id', $orgId)->orWhere('is_global', true);
            })
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'code' => "Mã tình trạng '{$code}' đã tồn tại.",
            ]);
        }
    }
}
