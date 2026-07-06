<?php

namespace Modules\LeadSource\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\LeadSource\Events\SourceDeleted;
use Modules\LeadSource\Models\LeadSource;

class DeleteSourceAction
{
    use AsAction;

    public function handle(LeadSource $source): void
    {
        if ($source->is_global) {
            throw ValidationException::withMessages([
                'source' => 'Không thể xóa nguồn toàn hệ thống.',
            ]);
        }

        $leadsCount = \DB::table('leads')->where('source_id', $source->id)->count();
        if ($leadsCount > 0) {
            throw ValidationException::withMessages([
                'source' => "Không thể xóa: có {$leadsCount} cơ hội đang dùng nguồn này.",
            ]);
        }

        $sourceId = $source->id;
        $orgId    = $source->organization_id;

        DB::transaction(fn () => $source->delete());

        event(new SourceDeleted($sourceId, $orgId));
    }
}
