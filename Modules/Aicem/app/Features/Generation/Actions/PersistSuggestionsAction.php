<?php

namespace Modules\Aicem\Features\Generation\Actions;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Aicem\Enums\SuggestionStatus;
use Modules\Aicem\Models\AicemGenerationRun;
use Modules\Aicem\Models\AicemSuggestion;

class PersistSuggestionsAction
{
    use AsAction;

    /** @param array<int, array{field: ?string, block_id: ?int, original_text: string, suggested_text: string, reason: string}> $validatedSuggestions */
    public function handle(AicemGenerationRun $run, array $validatedSuggestions): void
    {
        foreach ($validatedSuggestions as $item) {
            AicemSuggestion::create([
                'generation_run_id' => $run->id,
                'organization_id'   => $run->organization_id,
                'field'             => $item['field'],
                'block_id'          => $item['block_id'],
                'original_text'     => $item['original_text'],
                'suggested_text'    => $item['suggested_text'],
                'reason'            => $item['reason'],
                'status'            => SuggestionStatus::Pending,
            ]);
        }
    }
}
