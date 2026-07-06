<?php

namespace Modules\KcItem\Actions\Backend;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Modules\KcItem\Jobs\UpdateKcViewCountJob;
use Modules\KcItem\Models\KcItem;
use Modules\KcItem\Models\KcViewLog;

class LogKcViewAction
{
    /**
     * Log a view for the given KcItem.
     *
     * Dedup: if the same user has already viewed this item today, skip.
     * After insert, dispatch UpdateKcViewCountJob async.
     */
    public function handle(KcItem $kcItem, Request $request): void
    {
        $userId = auth()->id();

        // Dedup: skip if this user already viewed today
        if ($userId) {
            $alreadyViewed = KcViewLog::where('item_id', $kcItem->id)
                ->where('user_id', $userId)
                ->whereDate('viewed_at', today())
                ->exists();

            if ($alreadyViewed) {
                return;
            }
        }

        KcViewLog::create([
            'uuid'       => Str::uuid(),
            'item_id'    => $kcItem->id,
            'user_id'    => $userId,
            'session_id' => $request->session()->getId(),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'viewed_at'  => now(),
        ]);

        // Update view_count asynchronously
        UpdateKcViewCountJob::dispatch($kcItem->id);
    }
}
