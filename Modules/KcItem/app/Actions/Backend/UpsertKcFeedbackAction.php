<?php

namespace Modules\KcItem\Actions\Backend;

use Illuminate\Support\Str;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\KcItem\Models\KcFeedback;
use Modules\KcItem\Models\KcItem;

class UpsertKcFeedbackAction
{
    use AsAction;

    public function handle(KcItem $kcItem, array $data): KcFeedback
    {
        $feedback = KcFeedback::where('item_id', $kcItem->id)
            ->where('user_id', auth()->id())
            ->first();

        if ($feedback) {
            $feedback->update([
                'rating'     => $data['rating'] ?? $feedback->rating,
                'comment'    => $data['comment'] ?? $feedback->comment,
                'is_helpful' => isset($data['is_helpful']) ? (bool) $data['is_helpful'] : $feedback->is_helpful,
            ]);
        } else {
            $feedback = KcFeedback::create([
                'uuid'       => Str::uuid(),
                'item_id'    => $kcItem->id,
                'user_id'    => auth()->id(),
                'rating'     => $data['rating'] ?? null,
                'comment'    => $data['comment'] ?? null,
                'is_helpful' => isset($data['is_helpful']) ? (bool) $data['is_helpful'] : null,
            ]);
        }

        return $feedback;
    }
}
