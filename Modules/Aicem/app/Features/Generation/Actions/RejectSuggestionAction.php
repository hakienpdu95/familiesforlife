<?php

namespace Modules\Aicem\Features\Generation\Actions;

use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Aicem\Enums\SuggestionStatus;
use Modules\Aicem\Features\Generation\Exceptions\SuggestionAlreadyDecidedException;
use Modules\Aicem\Models\AicemSuggestion;

/**
 * Reject không ghi gì vào subject nên không cần guard staleness (mục 10), nhưng vẫn phải
 * lockForUpdate + re-fetch từ DB (không tin `$suggestion` truyền vào) để tránh race — VD 2 tab
 * cùng thao tác, hoặc gọi reject ngay sau accept trong cùng request mà không refresh model.
 */
class RejectSuggestionAction
{
    use AsAction;

    public function handle(AicemSuggestion $suggestion, int $userId): AicemSuggestion
    {
        return DB::transaction(function () use ($suggestion, $userId) {
            $suggestion = AicemSuggestion::query()->lockForUpdate()->findOrFail($suggestion->id);

            if (! in_array($suggestion->status, [SuggestionStatus::Pending, SuggestionStatus::Stale], true)) {
                throw new SuggestionAlreadyDecidedException(
                    "Đề xuất đã được quyết định trước đó ({$suggestion->status->label()})."
                );
            }

            $suggestion->update([
                'status'     => SuggestionStatus::Rejected,
                'decided_by' => $userId,
                'decided_at' => now(),
            ]);

            return $suggestion;
        });
    }
}
