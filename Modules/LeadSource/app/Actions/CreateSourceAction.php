<?php

namespace Modules\LeadSource\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\LeadSource\Data\Requests\CreateSourceData;
use Modules\LeadSource\Events\SourceCreated;
use Modules\LeadSource\Models\LeadSource;

class CreateSourceAction
{
    use AsAction;

    public function handle(CreateSourceData $data): LeadSource
    {
        $this->assertCodeUnique($data->code, $data->organization_id);

        $source = DB::transaction(fn () => LeadSource::create([
            'organization_id' => $data->organization_id,
            'is_global'       => false,
            'code'            => $data->code,
            'label'           => $data->label,
            'icon'            => $data->icon,
            'color'           => $data->color ?? 'gray',
            'sort_order'      => $data->sort_order,
            'is_active'       => true,
        ]));

        event(new SourceCreated($source));

        return $source;
    }

    private function assertCodeUnique(string $code, int $orgId): void
    {
        $exists = LeadSource::query()
            ->where('code', $code)
            ->where(function ($q) use ($orgId) {
                $q->where('organization_id', $orgId)->orWhere('is_global', true);
            })
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'code' => "Mã nguồn '{$code}' đã tồn tại.",
            ]);
        }
    }
}
